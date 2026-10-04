<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\Stage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\ApprovalController;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\Course;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\University;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Services\Applications\DocumentCatalogue;
use App\Services\Applications\FormSteps;
use App\Services\Applications\StageResolver;
use App\Services\Documents\DocumentStore;
use App\Support\Seo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ApplicationAdminController extends Controller
{
    public function __construct(private StageResolver $stages) {}

    public function index(Request $request)
    {
        $q = Application::with('user', 'tier', 'assignedStaff')->latest('last_activity_at');
        if ($s = $request->string('stage')->toString()) {
            $q->where('stage', $s);
        }
        if ($t = $request->string('q')->toString()) {
            $q->where(fn ($w) => $w->where('application_number', 'like', "%$t%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")));
        }

        return view('admin.applications.index', ['seo' => Seo::make('Applications')->noindex(), 'applications' => $q->paginate(30)->withQueryString(), 'stages' => Stage::cases(), 'filters' => $request->only('stage', 'q')]);
    }

    public function show(Application $application)
    {
        $this->stages->sync($application);
        $application->load('user', 'tier.prices', 'documents.currentVersion', 'documents.events', 'payments.tierPrice', 'submissions.university', 'submissions.course', 'submissions.events', 'submissions.authorisation', 'submissions.choices.course.university', 'messages.sender', 'events.actor', 'authorisations', 'assignedStaff');
        $application->messages->where('sender_user_id', $application->user_id)->whereNull('read_at')->each->update(['read_at' => now()]);

        return view('admin.applications.show', [
            'seo' => Seo::make($application->application_number)->noindex(), 'application' => $application,
            'staff' => User::whereIn('role', ['staff', 'admin'])->orderBy('name')->get(), 'catalogue' => DocumentCatalogue::TYPES,
            'universities' => University::orderBy('name')->get(), 'courses' => Course::with('university')->get(),
            'steps' => FormSteps::all(), 'routes' => Submission::ROUTES,
        ]);
    }

    public function assign(Request $request, Application $application)
    {
        $data = $request->validate(['assigned_staff_id' => 'nullable|exists:users,id', 'staff_notes' => 'nullable|string|max:5000']);
        $application->update($data);
        AdminAction::log('application.assign', $application, $data);

        return back()->with('status', 'Saved.');
    }

    public function stage(Request $request, Application $application)
    {
        $data = $request->validate(['stage_override' => 'nullable|in:INTERNAL_REVIEW,ACTION_REQUIRED,READY_FOR_STUDENT_APPROVAL,ON_HOLD,CLOSED,CLEAR', 'note' => 'nullable|string|max:2000', 'hold_until' => 'nullable|date|after:today']);
        $v = $data['stage_override'];
        if ($v === 'CLEAR') {
            $application->forceFill(['stage_override' => null, 'hold_until' => null])->save();
        } elseif ($v === 'ON_HOLD') {
            $application->forceFill(['stage_override' => $v, 'hold_until' => $data['hold_until'] ?? now()->addDays(30)])->save();
        } elseif ($v === 'CLOSED') {
            $application->forceFill(['stage_override' => $v, 'closed_at' => now(), 'closed_reason' => $data['note'] ?? null])->save();
            $application->reminders()->whereNull('sent_at')->update(['cancelled_at' => now()]);
        } else {
            if ($v === 'READY_FOR_STUDENT_APPROVAL' && ! $application->submissions()->where('status', 'PROPOSED')->exists()) {
                return back()->with('error', 'Propose a submission target first; the student must see where the application will go before approving.');
            }
            if (in_array($v, ['READY_FOR_STUDENT_APPROVAL', 'INTERNAL_REVIEW'], true)) {
                $derived = $this->stages->resolve($application->fresh(['documents', 'payments.tierPrice', 'submissions', 'tier', 'authorisations']));
                if (in_array($derived, [Stage::APPLICATION_STARTED, Stage::APPLICATION_INCOMPLETE, Stage::DOCUMENTS_INCOMPLETE, Stage::PAYMENT_REQUIRED], true)) {
                    return back()->with('error', 'The student\'s form, documents or payment are not complete ('.$derived->label().'). Approval and review can only begin once they are.');
                }
            }
            $application->forceFill(['stage_override' => $v])->save();
        }
        $application->record(match ($v) {
            'INTERNAL_REVIEW' => 'review.started', 'ACTION_REQUIRED' => 'action.required', 'READY_FOR_STUDENT_APPROVAL' => 'approval.requested', default => 'stage.changed'
        }, ['to' => $v, 'note' => $data['note'] ?? null], $request->user()->id);
        if (! empty($data['note'])) {
            $application->messages()->create(['sender_user_id' => $request->user()->id, 'body' => $data['note']]);
        }
        $this->stages->sync($application);
        if ($v === 'READY_FOR_STUDENT_APPROVAL') {
            $application->user->notify(new ApplicationNotification($application, 'approval.requested'));
        }
        if ($v === 'ACTION_REQUIRED') {
            $application->user->notify(new ApplicationNotification($application, 'action.required', ['note' => $data['note'] ?? null]));
        }
        AdminAction::log('application.stage', $application, $data);

        return back()->with('status', 'Stage updated.');
    }

    public function requestDocument(Request $request, Application $application)
    {
        $data = $request->validate(['code' => 'required|string|in:'.implode(',', array_keys(DocumentCatalogue::TYPES)), 'title' => 'nullable|string|max:160', 'reason' => 'required|string|max:500']);
        $doc = $application->documents()->firstOrNew(['code' => $data['code']]);
        $doc->fill(['title' => $data['title'] ?: DocumentCatalogue::title($data['code']), 'required_reason' => $data['reason'], 'requested_by' => $request->user()->id]);
        $doc->status = $doc->exists && $doc->current_version_id ? DocumentStatus::REPLACEMENT_REQUIRED : DocumentStatus::REQUIRED;
        $doc->staff_note = $data['reason'];
        $doc->save();
        $doc->events()->create(['from_status' => null, 'to_status' => $doc->status->value, 'actor_user_id' => $request->user()->id, 'reason' => $data['reason']]);
        $application->record('document.requested', ['title' => $doc->title, 'reason' => $data['reason']], $request->user()->id);
        $application->user->notify(new ApplicationNotification($application, 'document.requested', ['title' => $doc->title, 'reason' => $data['reason']]));
        $this->stages->sync($application);
        AdminAction::log('document.request', $doc, $data);

        return back()->with('status', 'Document requested; the student has been notified.');
    }

    public function reviewDocument(Request $request, Application $application, Document $document)
    {
        abort_unless($document->application_id === $application->id, 404);
        $data = $request->validate(['decision' => 'required|in:accept,reject,replace,waive', 'reason' => 'required_unless:decision,accept|nullable|string|max:500']);
        if ($data['decision'] === 'accept' && ! $document->currentVersion) {
            // An accepted document goes into the package the student approves; it must be a file someone reviewed.
            return back()->with('error', 'Nothing has been uploaded for "'.$document->title.'", so it cannot be accepted. Waive it if it is not needed.');
        }
        $to = match ($data['decision']) {
            'accept' => DocumentStatus::ACCEPTED, 'reject' => DocumentStatus::REJECTED, 'replace' => DocumentStatus::REPLACEMENT_REQUIRED, 'waive' => DocumentStatus::NOT_REQUIRED
        };
        $document->transition($to, $request->user()->id, $data['reason'] ?? null);
        $application->record($to === DocumentStatus::ACCEPTED ? 'document.accepted' : 'document.rejected', ['title' => $document->title, 'reason' => $data['reason'] ?? null], $request->user()->id);
        if ($to === DocumentStatus::ACCEPTED) {
            $application->user->notify(new ApplicationNotification($application, 'document.accepted', ['title' => $document->title]));
        }
        if (in_array($to, [DocumentStatus::REJECTED, DocumentStatus::REPLACEMENT_REQUIRED], true)) {
            $application->user->notify(new ApplicationNotification($application, 'document.rejected', ['title' => $document->title, 'reason' => $data['reason']]));
        }
        $before = $application->stage;
        $this->stages->sync($application->refresh());
        if ($application->stage === Stage::DOCUMENTS_COMPLETE && $before !== Stage::DOCUMENTS_COMPLETE) {
            $application->user->notify(new ApplicationNotification($application, 'documents.complete'));
        }
        AdminAction::log('document.review', $document, $data);

        return back()->with('status', 'Document '.$to->label().'.');
    }

    public function proposeSubmission(Request $request, Application $application)
    {
        $data = $request->validate(['university_id' => 'nullable|exists:universities,id', 'course_id' => 'nullable|exists:courses,id', 'intake' => 'required|string|max:32', 'route_code' => 'required|in:'.implode(',', array_keys(Submission::ROUTES)), 'choices' => 'nullable|string|max:1000', 'notes' => 'nullable|string|max:2000']);
        if ($data['route_code'] === 'DIRECT_AGENT' || $data['route_code'] === 'UCAS_CENTRE') {
            // Agreement-gated routes (docs/architecture/16.1). No agreements table entries exist yet → refuse.
            return back()->with('error', 'That route requires a signed, verified agreement on file for this university. None is recorded, so it cannot be offered.');
        }
        // Invalidate any live authorisation: the target changed
        foreach ($application->authorisations()->whereNull('revoked_at')->get() as $auth) {
            $auth->update(['revoked_at' => now(), 'revoked_reason' => 'Submission target changed by staff']);
            $application->record('authorisation.invalidated', ['authorisation_id' => $auth->id], $request->user()->id);
        }
        foreach ($application->submissions()->where('status', 'PROPOSED')->get() as $superseded) {
            $superseded->transition('CLOSED', $request->user()->id, 'Replaced by a new proposal');
        }
        $sub = $application->submissions()->create(['university_id' => $data['university_id'] ?? null, 'course_id' => $data['course_id'] ?? null, 'intake' => $data['intake'], 'route_code' => $data['route_code'], 'notes' => $data['notes'] ?? null, 'status' => 'PROPOSED']);
        $sub->events()->create(['from_status' => null, 'to_status' => 'PROPOSED', 'actor_user_id' => $request->user()->id, 'note' => 'Proposed by staff']);
        foreach (array_values(array_filter(array_map('trim', explode("\n", $data['choices'] ?? '')))) as $i => $label) {
            $sub->choices()->create(['label' => $label, 'choice_order' => $i + 1]);
        }
        AdminAction::log('submission.propose', $sub, $data);

        return back()->with('status', 'Submission proposed. Set the stage to "Ready for student approval" when the package is ready for the student to review.');
    }

    public function updateSubmission(Request $request, Application $application, Submission $submission)
    {
        abort_unless($submission->application_id === $application->id, 404);
        $data = $request->validate(['status' => 'required|in:PACKAGE_READY,SUBMITTED,ACKNOWLEDGED,INTERVIEW,OFFER_CONDITIONAL,OFFER_UNCONDITIONAL,REJECTED,WAITLISTED,ACCEPTED_BY_STUDENT,DECLINED,CLOSED', 'external_reference' => 'nullable|string|max:64', 'note' => 'nullable|string|max:2000']);
        // A university's response can only follow a submission: a proposal the student never approved cannot jump to
        // acknowledged, interview or offer (that would imply it was sent without approval). CLOSED is always allowed.
        $after = ['ACKNOWLEDGED', 'INTERVIEW', 'OFFER_CONDITIONAL', 'OFFER_UNCONDITIONAL', 'REJECTED', 'WAITLISTED', 'ACCEPTED_BY_STUDENT', 'DECLINED'];
        if (in_array($data['status'], $after, true) && ! in_array($submission->status, array_merge(['SUBMITTED'], $after), true)) {
            return back()->with('error', 'Record a university response only after the submission is marked submitted.');
        }
        if (in_array($data['status'], ['PACKAGE_READY', 'SUBMITTED'], true)) {
            // Invariant: never without a live student authorisation (docs/architecture/12.5)
            $auth = $submission->authorisation;
            if (! $auth || $auth->revoked_at) {
                return back()->with('error', 'This submission has no valid student authorisation. It cannot be marked ready or submitted.');
            }
            $current = hash('sha256', json_encode(ApprovalController::package($application, $submission->load('choices.course.university', 'university', 'course'))));
            if (! hash_equals($auth->snapshot_hash, $current)) {
                $auth->update(['revoked_at' => now(), 'revoked_reason' => 'Package changed after approval']);
                $submission->transition('PROPOSED', $request->user()->id, 'Approval invalidated: package changed');
                $application->forceFill(['stage_override' => Stage::READY_FOR_STUDENT_APPROVAL->value])->save();
                $application->record('authorisation.invalidated', ['submission_id' => $submission->id], $request->user()->id);
                $this->stages->sync($application);
                $application->user->notify(new ApplicationNotification($application, 'approval.requested'));

                return back()->with('error', 'The package changed after the student approved it. Their approval was cancelled and they have been asked to approve the updated package.');
            }
            if ($data['status'] === 'SUBMITTED' && in_array($submission->route_code, ['UCAS_STUDENT', 'DIRECT_PORTAL_STUDENT'], true) && empty($data['external_reference'])) {
                return back()->with('error', 'For student-submitted routes, record the UCAS Personal ID or university reference.');
            }
        }
        $submission->transition($data['status'], $request->user()->id, $data['note'] ?? null, $data['external_reference'] ?? null);
        if ($data['status'] === 'SUBMITTED') {
            $application->record('submission.sent', ['submission_id' => $submission->id], $request->user()->id);
            $application->user->notify(new ApplicationNotification($application, 'submission.sent', ['university' => $submission->university?->name ?? 'the university', 'course' => $submission->course?->title ?? 'Medicine']));
        } elseif (! in_array($data['status'], ['PACKAGE_READY', 'CLOSED'], true)) {
            $application->record('university.response', ['status' => $submission->statusLabel()], $request->user()->id);
            $application->user->notify(new ApplicationNotification($application, 'university.response', ['university' => $submission->university?->name ?? 'The university', 'status' => $submission->statusLabel(), 'note' => $data['note'] ?? null]));
        }
        $this->stages->sync($application);
        AdminAction::log('submission.update', $submission, $data);

        return back()->with('status', 'Submission updated.');
    }

    public function confirmPayment(Request $request, Application $application, Payment $payment)
    {
        abort_unless($payment->application_id === $application->id && $payment->method === 'MANUAL_TRANSFER', 404);
        $data = $request->validate(['decision' => 'required|in:confirm,reject', 'note' => 'nullable|string|max:500']);
        $payment->update(['status' => $data['decision'] === 'confirm' ? 'SUCCEEDED' : 'REJECTED', 'succeeded_at' => $data['decision'] === 'confirm' ? now() : null, 'note' => trim(($payment->note ?? '').' '.($data['note'] ?? ''))]);
        if ($data['decision'] === 'confirm') {
            $application->record('payment.succeeded', ['payment_id' => $payment->id, 'amount' => $payment->formattedAmount()], $request->user()->id);
            $application->user->notify(new ApplicationNotification($application, 'payment.succeeded', ['amount' => $payment->formattedAmount()]));
        }
        $this->stages->sync($application);
        AdminAction::log('payment.manual', $payment, $data);

        return back()->with('status', 'Payment '.($data['decision'] === 'confirm' ? 'confirmed' : 'rejected').'.');
    }

    public function reply(Request $request, Application $application)
    {
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $application->messages()->create(['sender_user_id' => $request->user()->id, 'body' => $data['body']]);
        $application->user->notify(new ApplicationNotification($application, 'message.received'));

        return back()->with('status', 'Reply sent.');
    }

    public function document(Request $request, Application $application, Document $document)
    {
        abort_unless($document->application_id === $application->id, 404);
        $v = $document->currentVersion;
        abort_unless($v, 404);
        \DB::table('document_access_log')->insert(['document_version_id' => $v->id, 'user_id' => $request->user()->id, 'ip' => $request->ip(), 'purpose' => 'preview', 'created_at' => now()]);
        $bytes = app(DocumentStore::class)->contents($v);

        // Inline preview for images/PDF inside a sandboxed iframe (CSP sandbox header); download for others
        $inline = in_array($v->mime, ['application/pdf', 'image/jpeg', 'image/png'], true);

        return response($bytes, 200, ['Content-Type' => $v->mime, 'Content-Disposition' => HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $v->safeFilename()), 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "sandbox; default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'", 'Cache-Control' => 'private, no-store']);
    }
}
