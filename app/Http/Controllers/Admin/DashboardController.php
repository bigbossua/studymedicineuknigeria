<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\ReferenceFact;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\University;
use App\Models\User;
use App\Services\Payments\StripeService;
use App\Support\Seo;

class DashboardController extends Controller
{
    /**
     * What still stands between the current configuration and a public launch, computed from the
     * database and config so the owner sees it inside the product rather than in a document.
     *
     * @return list<array{label:string, done:bool, detail:string, url:?string}>
     */
    private function launchChecklist(): array
    {
        $verified = ReferenceFact::where('verification_status', ReferenceFact::VERIFIED)->count();
        $pending = ReferenceFact::whereIn('verification_status', [ReferenceFact::VERIFY_ON_PAGE, ReferenceFact::REVIEW_DUE, ReferenceFact::SOURCE_CHANGED])->count();
        $published = University::where('published', true)->count();
        $universities = University::count();
        $priced = TierPrice::whereNotNull('amount_minor')->count();
        $tiers = ServiceTier::where('active', true)->count();
        $staffWith2fa = User::whereIn('role', ['staff', 'admin'])->whereNotNull('two_factor_confirmed_at')->count();
        $staff = User::whereIn('role', ['staff', 'admin'])->count();

        return [
            ['label' => 'Reference facts verified', 'done' => $pending === 0 && $verified > 0, 'detail' => "{$verified} verified · {$pending} awaiting verification", 'url' => route('admin.reference.sources')],
            ['label' => 'University pages published', 'done' => $published > 0, 'detail' => "{$published} of {$universities} marked indexable", 'url' => route('admin.reference.universities')],
            ['label' => 'Service prices set', 'done' => $priced > 0, 'detail' => $priced ? "{$priced} price(s) set" : "no prices yet across {$tiers} active tier(s); students can still apply", 'url' => route('admin.tiers')],
            ['label' => 'Card payments enabled', 'done' => app(StripeService::class)->enabled(), 'detail' => match (true) {
                ! config('services.stripe.secret') => 'STRIPE_SECRET not set: card payment closed (post-launch step)'.(config('site.bank_transfer') ? '; bank transfer open' : '; bank transfer off, so students cannot pay yet'),
                ! app(StripeService::class)->enabled() => 'a live Stripe key is refused outside production; use a test key (sk_test_)',
                str_starts_with((string) config('services.stripe.secret'), 'sk_test_') => 'Stripe test mode',
                default => 'Stripe live mode',
            }, 'url' => null],
            ['label' => 'Transactional email configured', 'done' => (bool) config('mail.mailers.smtp.password'), 'detail' => config('mail.mailers.smtp.password') ? 'SMTP password present' : (config('mail.default') === 'smtp' ? 'MAIL_PASSWORD not set' : 'development mailer ('.config('mail.default').'); set MAIL_* on the server'), 'url' => null],
            ['label' => 'All staff enrolled in two-step verification', 'done' => $staff > 0 && $staffWith2fa === $staff, 'detail' => "{$staffWith2fa} of {$staff} staff accounts", 'url' => route('admin.users')],
            ['label' => 'Legal pages reviewed', 'done' => (bool) config('site.legal_reviewed'), 'detail' => config('site.legal_reviewed') ? 'SITE_LEGAL_REVIEWED set' : 'privacy, terms, application terms and refunds are version 0.9 drafts', 'url' => route('legal.terms')],
            ['label' => 'Analytics decision made', 'done' => config('site.ga4_id') !== null, 'detail' => config('site.ga4_id') ? 'GA4 on with consent' : 'SITE_GA4_ID blank: first-party funnel only (valid choice; set to "off" to record the decision)', 'url' => route('admin.funnel')],
        ];
    }

    public function index()
    {
        return view('admin.dashboard', [
            'seo' => Seo::make('Admin')->noindex(),
            'counts' => [
                'leads_new' => Lead::where('status', 'new')->count(),
                'applications_active' => Application::whereNull('withdrawn_at')->whereNull('closed_at')->count(),
                'docs_review' => Document::whereIn('status', [DocumentStatus::UPLOADED, DocumentStatus::UNDER_REVIEW])->count(),
                'docs_complete_awaiting' => Application::where('stage', 'DOCUMENTS_COMPLETE')->count(),
                'approvals_pending' => Application::where('stage', 'READY_FOR_STUDENT_APPROVAL')->count(),
                'approved_to_submit' => Application::whereIn('stage', ['STUDENT_APPROVED', 'READY_FOR_UNIVERSITY_SUBMISSION'])->count(),
                'payments_manual' => Payment::where('status', 'MANUAL_REVIEW')->count(),
                'facts_pending' => ReferenceFact::where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->count(),
                'facts_review_due' => ReferenceFact::where('verification_status', ReferenceFact::REVIEW_DUE)->count(),
            ],
            'recent' => Application::with('user', 'tier')->latest('last_activity_at')->take(10)->get(),
            'byStage' => Application::selectRaw('stage, count(*) c')->groupBy('stage')->pluck('c', 'stage'),
            'launch' => $this->launchChecklist(),
        ]);
    }
}
