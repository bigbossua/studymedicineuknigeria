<?php

namespace App\Console\Commands;

use App\Enums\Stage;
use App\Models\Application;
use App\Models\DocumentVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The retention rules of the privacy notice, applied weekly:
 * - document files are deleted 12 months after an application closes (withdrawn, closed or completed);
 * - the application's own content (form answers, staff notes, message text) is anonymised after 24 months, keeping
 *   dates, stages, payment records and approval records (kept 6 years for accounting and evidence);
 * - document access logs older than 24 months are deleted.
 * Counts only are logged; nothing personal.
 */
class ApplyRetention extends Command
{
    protected $signature = 'smukn:retention {--dry-run : Report what would be removed without changing anything}';

    protected $description = 'Delete closed applications\' documents after 12 months and anonymise their content after 24 (privacy notice)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $closedBefore = fn (int $months) => Application::whereIn('stage', [Stage::WITHDRAWN, Stage::CLOSED, Stage::COMPLETED])
            ->whereRaw('COALESCE(closed_at, withdrawn_at, updated_at) < ?', [now()->subMonths($months)]);

        $versions = DocumentVersion::whereNull('purged_at')->whereHas('document', fn ($q) => $q->whereIn('application_id', $closedBefore(12)->select('id')))->get();
        $toAnonymise = $closedBefore(24)->whereNull('anonymised_at')->get();
        $oldLogs = DB::table('document_access_log')->where('created_at', '<', now()->subMonths(24));
        $summary = ['document_files' => $versions->count(), 'applications_anonymised' => $toAnonymise->count(), 'access_log_rows' => $oldLogs->count()];

        if (! $dry) {
            foreach ($versions as $v) {
                Storage::disk($v->disk)->delete($v->path);
                $v->forceFill(['path' => '', 'purged_at' => now()])->save();
            }
            foreach ($toAnonymise as $a) {
                DB::transaction(function () use ($a) {
                    $a->forceFill(['form' => null, 'staff_notes' => null, 'anonymised_at' => now()])->save();
                    DB::table('messages')->where('application_id', $a->id)->update(['body' => '[removed under the retention policy]', 'attachment_version_id' => null]);
                });
            }
            $oldLogs->delete();
            Log::info('retention.applied', $summary);
        }
        $this->info(($dry ? 'Would remove: ' : 'Removed: ').collect($summary)->map(fn ($n, $k) => "$k $n")->implode(', ').'.');

        return self::SUCCESS;
    }
}
