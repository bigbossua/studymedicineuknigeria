<?php

namespace App\Services\Privacy;

use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Removes personal and free-text data while keeping the dated skeleton the privacy notice keeps as evidence
 * (stages, dates, amounts, the approval fingerprint). Used by the weekly retention job and by account erasure.
 */
final class Anonymiser
{
    public const REMOVED = '[removed under the retention policy]';

    /** application_events payload keys that carry no personal data. */
    private const EVENT_KEEP = ['step', 'route_code', 'status', 'amount', 'tier', 'from', 'to', 'stage', 'payment_id', 'document_id', 'submission_id', 'refunded'];

    public static function application(Application $a): void
    {
        $a->forceFill(['form' => null, 'staff_notes' => null, 'closed_reason' => $a->closed_reason ? self::REMOVED : null, 'anonymised_at' => now()])->save();
        DB::table('messages')->where('application_id', $a->id)->update(['body' => self::REMOVED, 'attachment_version_id' => null]);
        foreach (DB::table('application_events')->where('application_id', $a->id)->whereNotNull('payload')->get(['id', 'payload']) as $e) {
            $payload = json_decode((string) $e->payload, true);
            $kept = is_array($payload) ? array_intersect_key($payload, array_flip(self::EVENT_KEEP)) : [];
            DB::table('application_events')->where('id', $e->id)->update(['payload' => $kept ? json_encode($kept) : null]);
        }
        $documentIds = DB::table('documents')->where('application_id', $a->id)->pluck('id');
        DB::table('documents')->whereIn('id', $documentIds)->update(['title' => 'Document', 'required_reason' => null, 'staff_note' => null]);
        DB::table('document_versions')->whereIn('document_id', $documentIds)->update(['original_filename' => 'removed']);
        DB::table('document_events')->whereIn('document_id', $documentIds)->update(['reason' => null]);
        $submissionIds = DB::table('submissions')->where('application_id', $a->id)->pluck('id');
        DB::table('submissions')->whereIn('id', $submissionIds)->update(['notes' => null, 'external_reference' => null]);
        DB::table('submission_events')->whereIn('submission_id', $submissionIds)->update(['note' => null, 'external_reference' => null]);
        DB::table('payments')->where('application_id', $a->id)->update(['note' => null]);
        DB::table('authorisations')->where('application_id', $a->id)->update(['snapshot' => json_encode(['removed' => 'retention policy']), 'typed_name' => self::REMOVED, 'revoked_reason' => null]); // snapshot_hash stays as the fingerprint
        DB::table('admin_actions')->where('target_type', Application::class)->where('target_id', $a->id)->update(['payload' => null]);
    }

    /** The account row stays (payments and approvals refer to it) but carries nothing that identifies the person. */
    public static function user(int $id): void
    {
        $email = DB::table('users')->where('id', $id)->value('email');
        DB::table('users')->where('id', $id)->update(['name' => 'Removed account', 'email' => "removed-{$id}@invalid", 'phone' => null, 'whatsapp' => null,
            'password' => bcrypt(Str::random(40)), 'remember_token' => null, 'two_factor_secret' => null, 'updated_at' => now()]);
        DB::table('leads')->where('user_id', $id)->orWhere('email', $email)->delete();
        DB::table('sessions')->where('user_id', $id)->delete();
        DB::table('funnel_events')->where('user_id', $id)->update(['user_id' => null]);
    }
}
