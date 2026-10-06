<?php

namespace App\Support;

use App\Models\Application;
use App\Models\FunnelEvent;
use App\Services\Applications\FormSteps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records the reporting events of docs/architecture/12.9 in the funnel_events table and, for the
 * public-site steps only, queues them for the consent-gated GA4 layer. Never throws: analytics
 * must not break a student's request.
 */
final class Funnel
{
    /** Events that may also be sent to GA4 from the browser (public site, after consent). lead_created = eligibility check completed. */
    private const CLIENT_EVENTS = ['course_viewed', 'apply_viewed', 'lead_created', 'account_created'];

    /**
     * Application-journey events GA4 may receive from the server (Measurement Protocol), never from a script in the
     * portal: only when the visitor accepted analytics and GA4 has given the browser its anonymous client id. Only the
     * parameters in clientParams() travel: no names, emails, application numbers, documents or free text.
     */
    private const SERVER_EVENTS = ['application_started', 'step_completed', 'document_uploaded', 'service_chosen', 'student_approved', 'submitted', 'payment_started', 'payment_completed'];

    /** application_events type → reporting event name, with the payload keys worth keeping. */
    private const FROM_APPLICATION_EVENT = [
        'application.started' => 'application_started',
        'step.completed' => 'step_completed',
        'document.uploaded' => 'document_uploaded',
        'document.accepted' => 'document_accepted',
        'document.rejected' => 'document_rejected',
        'service.chosen' => 'service_chosen',
        'service.changed' => 'service_chosen',
        'payment.initiated' => 'payment_started',
        'payment.manual_requested' => 'payment_started',
        'payment.succeeded' => 'payment_completed',
        'approval.requested' => 'approval_requested',
        'student.approved' => 'student_approved',
        'submission.sent' => 'submitted',
        'university.response' => 'university_response',
        'application.withdrawn' => 'application_withdrawn',
    ];

    public static function track(string $name, array $properties = [], ?Application $application = null, ?int $userId = null): void
    {
        try {
            // In a console command the container still holds a placeholder request; only a routed request counts.
            $request = app()->bound('request') && request()->route() ? request() : null;
            if ($request?->headers->has('X-SMUKN-Build')) {
                return; // OG/image build requests are not visitors
            }
            FunnelEvent::create([
                'name' => $name,
                'occurred_at' => now(),
                'visitor_hash' => self::visitorHash(),
                'user_id' => $userId ?? $application?->user_id ?? $request?->user()?->id,
                'application_hash' => $application ? self::applicationHash($application) : null,
                'tier' => $application?->tier?->code ?? ($properties['tier'] ?? null),
                'intake_year' => $application?->intake_year ?? ($properties['intake_year'] ?? null),
                'source_page' => $request ? self::maskedPath($request->path()) : null,
                'utm' => $request ? array_filter($request->only('utm_source', 'utm_medium', 'utm_campaign')) ?: null : null,
                'properties' => $properties ?: null,
            ]);
            if ($request && in_array($name, self::CLIENT_EVENTS, true) && $request->hasSession()) {
                $request->session()->push('funnel.client', ['name' => $name, 'params' => self::clientParams($properties)]);
            }
            // GA4 sees only what the student does in their own browser: a staff action or a webhook is never sent
            if ($request && in_array($name, self::SERVER_EVENTS, true) && ($properties['actor'] ?? 'student') === 'student') {
                self::toGa4($request, $name, array_filter(self::clientParams($properties + ['tier' => $application?->tier?->code, 'intake_year' => $application?->intake_year]), fn ($v) => $v !== null));
            }
        } catch (Throwable $e) {
            Log::warning('funnel.track_failed', ['name' => $name, 'error' => $e->getMessage()]);
        }
    }

    public static function fromApplicationEvent(Application $application, string $type, array $payload, ?int $actorId): void
    {
        $name = self::FROM_APPLICATION_EVENT[$type] ?? null;
        if (! $name) {
            return;
        }
        $keep = array_intersect_key($payload, array_flip(['step', 'route_code', 'status', 'amount']));
        self::track($name, $keep + ['actor' => $actorId === $application->user_id ? 'student' : 'staff'], $application);
    }

    /** Keyed (HMAC) so the sequential application number cannot be recovered by hashing every possible number. */
    public static function applicationHash(Application $application): string
    {
        return hash_hmac('sha256', (string) $application->application_number, (string) config('app.key'));
    }

    /** The page path without the application number (portal and admin URLs carry it). */
    public static function maskedPath(string $path): string
    {
        return preg_replace('/SMUKN-\d{4}-\d{6}/i', '{application}', $path) ?? $path;
    }

    /** Measurement Protocol hit for a consented visitor, sent after the response so the student never waits for it. */
    private static function toGa4(Request $request, string $name, array $params): void
    {
        $id = config('site.ga4_id');
        $secret = config('site.ga4_api_secret');
        if (! $id || ! $secret || $request->cookie('smukn_consent') !== 'granted') {
            return;
        }
        // _ga is "GA1.1.<random>.<timestamp>"; the client id is its last two parts, an anonymous browser identifier
        if (! preg_match('/^GA\d\.\d\.(\d+\.\d+)$/', (string) $request->cookie('_ga'), $m)) {
            return;
        }
        $clientId = $m[1];
        dispatch(function () use ($id, $secret, $clientId, $name, $params) {
            try {
                Http::timeout(3)->post('https://www.google-analytics.com/mp/collect?'.http_build_query(['measurement_id' => $id, 'api_secret' => $secret]),
                    ['client_id' => $clientId, 'non_personalized_ads' => true, 'events' => [['name' => $name, 'params' => $params + ['engagement_time_msec' => 1]]]]);
            } catch (Throwable $e) {
                Log::warning('funnel.ga4_failed', ['name' => $name, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    /** Events queued for the GA4 layer on this session, cleared on read. */
    public static function pullClientEvents(): array
    {
        try {
            return request()->hasSession() ? (array) request()->session()->pull('funnel.client', []) : [];
        } catch (Throwable) {
            return [];
        }
    }

    private static function visitorHash(): ?string
    {
        try {
            $id = request()->hasSession() ? request()->session()->getId() : null;

            return $id ? hash_hmac('sha256', $id, (string) config('app.key')) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Only low-cardinality, non-identifying parameters go to GA4: step is a fixed form-section key (personal,
     * education…), never a document title, a name or anything the student typed.
     */
    private static function clientParams(array $properties): array
    {
        $params = array_intersect_key($properties, array_flip(['qualification', 'intake_year', 'tier', 'route', 'school', 'step']));
        if (isset($params['step']) && ! array_key_exists($params['step'], FormSteps::all())) {
            unset($params['step']);
        }

        return $params;
    }
}
