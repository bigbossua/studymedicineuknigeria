<?php

namespace App\Services\Applications;

use App\Enums\DocumentStatus;
use App\Enums\Stage;
use App\Models\Application;

/**
 * deriveStage() — a pure function over the application and its children (docs/architecture/12.4).
 * Staff-set judgement stages (stage_override) win over derived stages while they apply.
 */
class StageResolver
{
    public function resolve(Application $a): Stage
    {
        $a->loadMissing(['documents', 'payments.tierPrice', 'submissions', 'tier', 'authorisations']);

        if ($a->withdrawn_at) {
            return Stage::WITHDRAWN;
        }
        if ($a->closed_at) {
            return Stage::CLOSED;
        }
        if ($a->hold_until && $a->hold_until->isFuture()) {
            return Stage::ON_HOLD;
        }

        $gate = $a->tier?->payment_gate ?? 'AT_START';
        $paid = $a->hasSucceededPayment();
        // A fee is due only once our team has approved the profile and the student can see and choose a service.
        $needsPay = $a->servicesApproved() && ! $paid && (! $a->tier || $a->tier->hasPrices());

        if ($gate === 'AT_START' && $needsPay) {
            return Stage::PAYMENT_REQUIRED;
        }

        $formComplete = collect(array_keys(FormSteps::all()))->every(fn ($s) => $a->sectionComplete($s));
        if (! $formComplete) {
            return $a->form ? Stage::APPLICATION_INCOMPLETE : Stage::APPLICATION_STARTED;
        }

        $required = $a->documents->filter(fn ($d) => $d->status !== DocumentStatus::NOT_REQUIRED);
        $docsComplete = $required->isNotEmpty() && $required->every(fn ($d) => $d->status === DocumentStatus::ACCEPTED);
        if (! $docsComplete) {
            return Stage::DOCUMENTS_INCOMPLETE;
        }

        if ($gate === 'BEFORE_REVIEW' && $needsPay) {
            return Stage::PAYMENT_REQUIRED;
        }

        // Staff judgement stages
        $override = $a->stage_override ? Stage::tryFrom($a->stage_override) : null;
        if ($override === Stage::ACTION_REQUIRED) {
            return Stage::ACTION_REQUIRED;
        }

        $sub = $a->submissions->sortByDesc('id')->first();
        if (! $sub || $sub->status === 'PROPOSED') {
            if ($override === Stage::READY_FOR_STUDENT_APPROVAL && $sub) {
                return Stage::READY_FOR_STUDENT_APPROVAL;
            }

            return $override === Stage::INTERNAL_REVIEW ? Stage::INTERNAL_REVIEW : Stage::DOCUMENTS_COMPLETE;
        }

        if ($sub->status === 'AUTHORISED' || $sub->status === 'PACKAGE_READY') {
            if ($gate === 'BEFORE_SUBMISSION' && ! $a->hasSucceededPayment('submission') && ! $paid) {
                return Stage::PAYMENT_REQUIRED;
            }

            return $sub->status === 'PACKAGE_READY' ? Stage::READY_FOR_UNIVERSITY_SUBMISSION : Stage::STUDENT_APPROVED;
        }
        if ($sub->status === 'SUBMITTED') {
            return Stage::SUBMITTED;
        }
        if ($sub->status === 'ACKNOWLEDGED') {
            return Stage::UNIVERSITY_ACKNOWLEDGED;
        }
        if (in_array($sub->status, ['INTERVIEW', 'OFFER_CONDITIONAL', 'OFFER_UNCONDITIONAL', 'WAITLISTED', 'REJECTED'], true)) {
            return Stage::UNIVERSITY_STAGE;
        }
        if (in_array($sub->status, ['ACCEPTED_BY_STUDENT', 'DECLINED', 'CLOSED'], true)) {
            return Stage::COMPLETED;
        }

        return Stage::DOCUMENTS_COMPLETE;
    }

    /** Completion %: form 50, documents 40, approval 10 (configurable weights). */
    public function completion(Application $a): int
    {
        $steps = array_keys(FormSteps::all());
        $formPct = count($steps) ? collect($steps)->filter(fn ($s) => $a->sectionComplete($s))->count() / count($steps) : 0;
        $req = $a->documents->filter(fn ($d) => $d->status !== DocumentStatus::NOT_REQUIRED);
        $docPct = $req->isEmpty() ? 0 : $req->filter(fn ($d) => $d->status === DocumentStatus::ACCEPTED)->count() / $req->count();
        $approved = $a->authorisations->contains(fn ($x) => $x->revoked_at === null) ? 1 : 0;

        return (int) round($formPct * 50 + $docPct * 40 + $approved * 10);
    }

    /** Persist derived stage + completion. */
    public function sync(Application $a): Application
    {
        $stage = $this->resolve($a);
        $a->forceFill(['stage' => $stage, 'completion_pct' => $this->completion($a)])->saveQuietly();

        return $a;
    }

    /** The single most important line on the dashboard (docs/architecture/12.7). Returns [label, url, kind]. */
    public function nextAction(Application $a): array
    {
        $stage = $a->stage instanceof Stage ? $a->stage : Stage::from($a->stage);
        $num = $a->application_number;
        $sub = $a->submissions->sortByDesc('id')->first();

        if ($stage === Stage::READY_FOR_STUDENT_APPROVAL) {
            return ['Review and approve your application', route('portal.approve.show', $num), 'primary'];
        }
        if ($stage === Stage::PAYMENT_REQUIRED) {
            return $a->tier
                ? ['Confirm and pay for '.$a->tier->name, route('portal.payments.index', $num), 'primary']
                : ['Your profile has been reviewed: choose your service', route('portal.services.index', $num), 'primary'];
        }
        $doc = $a->documents->first(fn ($d) => $d->status->needsStudent());
        if ($doc) {
            return ['Upload your '.lcfirst($doc->title), route('portal.documents.show', [$num, $doc->id]), 'primary'];
        }
        foreach (FormSteps::all() as $key => $meta) {
            if (! $a->sectionComplete($key)) {
                return ['Continue: '.$meta['title'], route('portal.application.step', [$num, $key]), 'primary'];
            }
        }
        if (! $a->servicesApproved() && ! $a->hasSucceededPayment() && ! $a->isTerminal()) {
            return ['Your profile is with our team for review. Once it is reviewed, your portal shows the service options and their fees.', null, 'info'];
        }
        if ($stage === Stage::ACTION_REQUIRED) {
            return ['Read the message from our team and respond', route('portal.messages.index', $num), 'primary'];
        }
        if ($stage === Stage::INTERNAL_REVIEW) {
            return ['We are reviewing your file. Nothing is needed from you right now.', null, 'info'];
        }
        if ($stage === Stage::DOCUMENTS_COMPLETE) {
            return ['Your documents are complete. Our team will review your file and propose a submission.', null, 'info'];
        }
        if ($stage === Stage::STUDENT_APPROVED || $stage === Stage::READY_FOR_UNIVERSITY_SUBMISSION) {
            return ['Approved. We are preparing your package for submission.', route('portal.submissions.index', $num), 'info'];
        }
        if ($stage === Stage::SUBMITTED && $sub) {
            return ['Submitted to '.($sub->university?->name ?? 'the university').' on '.$sub->submitted_at?->format('j F Y').'. We will update you when the university responds.', route('portal.submissions.index', $num), 'info'];
        }
        if ($stage === Stage::UNIVERSITY_ACKNOWLEDGED || $stage === Stage::UNIVERSITY_STAGE) {
            return ['Your application is with the university. Check the submissions page for the latest status.', route('portal.submissions.index', $num), 'info'];
        }
        if ($stage === Stage::ON_HOLD) {
            return ['Your application is on hold until '.$a->hold_until?->format('j F Y').'.', null, 'info'];
        }
        if ($stage === Stage::WITHDRAWN) {
            return ['You withdrew this application.', null, 'info'];
        }

        return [$stage->label(), null, 'info'];
    }
}
