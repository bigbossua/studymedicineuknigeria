<?php

namespace App\Support;

use App\Models\Application;
use App\Models\FunnelEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records the reporting events of docs/architecture/12.9 in the funnel_events table and, for the
 * public-site steps only, queues them for the consent-gated GA4 layer. Never throws: analytics
 * must not break a student's request.
 */
final class Funnel
{
    /** Events that may also be sent to GA4 (public site, after consent). Portal events stay first-party only. */
    private const CLIENT_EVENTS = ['lead_created', 'account_created'];

    /** application_events type → reporting event name, with the payload keys worth keeping. */
    private const FROM_APPLICATION_EVENT = [
        'application.started' => 'application_started',
        'step.completed' => 'step_completed',
        'document.uploaded' => 'document_uploaded',
        'document.accepted' => 'document_accepted',
        'document.rejected' => 'document_rejected',
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
            FunnelEvent::create([
                'name' => $name,
                'occurred_at' => now(),
                'visitor_hash' => self::visitorHash(),
                'user_id' => $userId ?? $application?->user_id ?? $request?->user()?->id,
                'application_hash' => $application ? hash('sha256', $application->application_number) : null,
                'tier' => $application?->tier?->code ?? ($properties['tier'] ?? null),
                'intake_year' => $application?->intake_year ?? ($properties['intake_year'] ?? null),
                'source_page' => $request?->path(),
                'utm' => $request ? array_filter($request->only('utm_source', 'utm_medium', 'utm_campaign')) ?: null : null,
                'properties' => $properties ?: null,
            ]);
            if ($request && in_array($name, self::CLIENT_EVENTS, true) && $request->hasSession()) {
                $request->session()->push('funnel.client', ['name' => $name, 'params' => self::clientParams($properties)]);
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
        $keep = array_intersect_key($payload, array_flip(['step', 'title', 'route_code', 'status', 'amount']));
        self::track($name, $keep + ['actor' => $actorId === $application->user_id ? 'student' : 'staff'], $application);
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

    /** Only low-cardinality, non-identifying parameters go client-side. */
    private static function clientParams(array $properties): array
    {
        return array_intersect_key($properties, array_flip(['qualification', 'intake_year', 'tier', 'route']));
    }
}
