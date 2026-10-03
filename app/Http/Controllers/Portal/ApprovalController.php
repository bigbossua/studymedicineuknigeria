<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentStatus;
use App\Enums\Stage;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Authorisation;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Notifications\StaffNotification;
use App\Services\Applications\StageResolver;
use App\Support\Seo;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private StageResolver $stages) {}

    public function show(Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $this->stages->sync($application);
        $submission = $application->submissions()->with('university', 'course', 'choices.course.university')->where('status', 'PROPOSED')->latest('id')->first();
        abort_unless($application->stage === Stage::READY_FOR_STUDENT_APPROVAL && $submission, 404);
        $package = $this->package($application, $submission);

        return view('portal.approve', ['seo' => Seo::make('Review and approve your application')->noindex(), 'application' => $application, 'submission' => $submission, 'package' => $package,
            'hash' => hash('sha256', json_encode($package)), 'declaration' => Authorisation::declarationText($submission->university?->name ?? 'the university', $submission->course?->title ?? 'Medicine', $submission->intake ?? $application->intake_year.' entry'),
            'docs' => $application->documents()->where('status', DocumentStatus::ACCEPTED)->with('currentVersion')->get()]);
    }

    public function approve(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $submission = $application->submissions()->where('status', 'PROPOSED')->latest('id')->first();
        abort_unless($submission, 404);
        $data = $request->validate(['typed_name' => 'required|string|max:160', 'confirm' => 'required|accepted', 'hash' => 'required|string']);
        $package = $this->package($application, $submission);
        $hash = hash('sha256', json_encode($package));
        if (! hash_equals($hash, $data['hash'])) {
            return back()->with('error', 'Your application changed while you were reviewing it. Please review the updated package and approve again.');
        }

        $auth = Authorisation::create([
            'application_id' => $application->id, 'submission_id' => $submission->id, 'approved_by_user_id' => $request->user()->id, 'approved_at' => now(),
            'ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 512), 'declaration_version' => Authorisation::DECLARATION_VERSION,
            'typed_name' => $data['typed_name'], 'snapshot_hash' => $hash, 'snapshot' => $package,
        ]);
        $submission->forceFill(['authorisation_id' => $auth->id, 'package_hash' => $hash, 'package_version' => $submission->package_version + 1])->save();
        $submission->transition('AUTHORISED', $request->user()->id, 'Student approved; declaration '.Authorisation::DECLARATION_VERSION);
        $application->forceFill(['stage_override' => null])->save();
        $application->record('student.approved', ['authorisation_id' => $auth->id, 'submission_id' => $submission->id], $request->user()->id);
        $this->stages->sync($application);
        $request->user()->notify(new ApplicationNotification($application, 'student.approved', ['at' => now()->format('j F Y, H:i')]));
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('Student approved — '.$application->application_number, [$request->user()->name.' approved submission to '.($submission->university?->name ?? 'university').'.'], route('admin.applications.show', $application)));

        return redirect()->route('portal.submissions.index', $application)->with('status', 'Thank you. Your approval has been recorded. We will now prepare the final package.');
    }

    public function requestChanges(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $data = $request->validate(['note' => 'required|string|max:2000']);
        $application->messages()->create(['sender_user_id' => $request->user()->id, 'body' => "Change requested before approval:\n\n".$data['note']]);
        $application->forceFill(['stage_override' => Stage::ACTION_REQUIRED->value])->save();
        $application->record('approval.declined', ['note' => $data['note']], $request->user()->id);
        $this->stages->sync($application);
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('Changes requested before approval — '.$application->application_number, [$data['note']], route('admin.applications.show', $application)));

        return redirect()->route('portal.dashboard')->with('status', 'We have received your request. Our team will update the package and ask you to review it again.');
    }

    /** Canonical snapshot (docs/architecture/16.3). Any change here changes the hash and invalidates approvals. */
    public static function package(Application $application, $submission): array
    {
        $docs = $application->documents()->where('status', DocumentStatus::ACCEPTED)->with('currentVersion')->get()
            ->map(fn ($d) => ['code' => $d->code, 'title' => $d->title, 'version' => $d->currentVersion?->version, 'sha256' => $d->currentVersion?->sha256])->values()->all();

        return [
            'application_number' => $application->application_number,
            'target' => ['university' => $submission->university?->name, 'course' => $submission->course?->title, 'ucas_code' => $submission->course?->ucas_code, 'intake' => $submission->intake, 'route' => $submission->route_code,
                'choices' => $submission->choices->map(fn ($c) => $c->label ?? ($c->course?->university?->name.' — '.$c->course?->title))->values()->all()],
            'form' => $application->form,
            'documents' => $docs,
            'tier' => $application->tier?->name,
            'payments' => $application->payments->where('status', 'SUCCEEDED')->map(fn ($p) => $p->formattedAmount())->values()->all(),
        ];
    }
}
