<?php

namespace App\Console\Commands;

use App\Enums\Stage;
use App\Models\Application;
use App\Models\DocumentVersion;
use App\Services\Privacy\Anonymiser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The retention rules of the privacy notice, applied weekly:
 * - an application not yet submitted with no activity for 24 months is closed ("No activity for 24 months");
 * - document files are deleted 12 months after an application closes (withdrawn, closed or completed);
 * - 24 months after closing, every free-text and personal field of the application is anonymised: form answers, staff
 *   notes, message text, event notes, document titles, notes and filenames, submission notes and references, payment
 *   notes, the approval snapshot and typed name (the snapshot's fingerprint and the dates stay as evidence);
 * - payment, approval and Stripe event records are deleted 6 years after the application closed;
 * - eligibility-check records (leads) and first-party analytics events are deleted after 24 months, and a student
 *   account with nothing left to keep is anonymised 24 months after its last activity;
 * - document access logs older than 24 months are deleted.
 * Counts only are logged; nothing personal. The last run is kept for Admin → Launch (Cache 'retention.last_run').
 */
class ApplyRetention extends Command
{
    protected $signature = 'smukn:retention {--dry-run : Report what would be removed without changing anything}';

    protected $description = 'Apply the privacy notice retention rules (documents 12 months, anonymisation 24 months, payment records 6 years)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $open = array_map(fn (Stage $s) => $s->value, array_filter(Stage::cases(), fn (Stage $s) => ! in_array($s, [Stage::SUBMITTED, Stage::UNIVERSITY_ACKNOWLEDGED, Stage::UNIVERSITY_STAGE, Stage::COMPLETED, Stage::WITHDRAWN, Stage::CLOSED], true)));
        $inactive = Application::whereIn('stage', $open)->whereRaw('COALESCE(last_activity_at, updated_at) < ?', [now()->subMonths(24)])->get();
        $closedBefore = fn (int $months) => Application::whereIn('stage', [Stage::WITHDRAWN, Stage::CLOSED, Stage::COMPLETED])
            ->whereRaw('COALESCE(closed_at, withdrawn_at, updated_at) < ?', [now()->subMonths($months)]);

        $versions = DocumentVersion::whereNull('purged_at')->whereHas('document', fn ($q) => $q->whereIn('application_id', $closedBefore(12)->select('id')))->get();
        $toAnonymise = $closedBefore(24)->whereNull('anonymised_at')->get();
        $sixYears = $closedBefore(72)->pluck('id');
        $oldLogs = DB::table('document_access_log')->where('created_at', '<', now()->subMonths(24));
        $oldLeads = DB::table('leads')->where('updated_at', '<', now()->subMonths(24));
        $oldEvents = DB::table('funnel_events')->where('occurred_at', '<', now()->subMonths(24));
        $oldStripe = DB::table('stripe_events')->where('created_at', '<', now()->subYears(6));
        $staleUsers = DB::table('users')->where('role', 'student')->where('updated_at', '<', now()->subMonths(24))->where('email', 'not like', 'removed-%@invalid')->where('role', 'student')
            ->whereNotExists(fn ($q) => $q->from('applications')->whereColumn('applications.user_id', 'users.id')->whereNull('anonymised_at'));

        $summary = [
            'applications_closed_inactive' => $inactive->count(), 'document_files' => $versions->count(), 'applications_anonymised' => $toAnonymise->count(),
            'payment_records_deleted' => DB::table('payments')->whereIn('application_id', $sixYears)->count(), 'access_log_rows' => $oldLogs->count(),
            'leads_deleted' => $oldLeads->count(), 'analytics_events_deleted' => $oldEvents->count(), 'stripe_events_deleted' => $oldStripe->count(), 'accounts_anonymised' => $staleUsers->count(),
        ];

        if (! $dry) {
            foreach ($inactive as $a) {
                $a->forceFill(['stage' => Stage::CLOSED, 'closed_at' => now(), 'closed_reason' => 'No activity for 24 months'])->save();
                $a->record('application.closed', ['reason' => 'inactive']);
            }
            foreach ($versions as $v) {
                Storage::disk($v->disk)->delete($v->path);
                $v->forceFill(['path' => '', 'purged_at' => now()])->save();
            }
            foreach ($toAnonymise as $a) {
                DB::transaction(fn () => Anonymiser::application($a));
            }
            DB::transaction(function () use ($sixYears) {
                DB::table('payments')->whereIn('application_id', $sixYears)->delete();
                DB::table('authorisations')->whereIn('application_id', $sixYears)->delete();
            });
            $oldLogs->delete();
            $oldLeads->delete();
            $oldEvents->delete();
            $oldStripe->delete();
            foreach ($staleUsers->pluck('id') as $id) {
                Anonymiser::user($id);
            }
            Log::warning('retention.applied', $summary); // warning: production logs at warning level, so every run is on record
            Cache::forever('retention.last_run', ['at' => now()->toIso8601String()] + $summary);
        }
        $this->info(($dry ? 'Would remove: ' : 'Removed: ').collect($summary)->map(fn ($n, $k) => "$k $n")->implode(', ').'.');

        return self::SUCCESS;
    }
}
