<?php

namespace App\Console\Commands;

use App\Enums\Stage;
use App\Models\Application;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Privacy\Anonymiser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Completes a student's deletion request (privacy notice: "ask us to delete your account"): withdraws open
 * applications, deletes every document file, anonymises the applications and the account, and removes the
 * eligibility-check records. Payment and approval records keep their dates and amounts for the 6-year accounting
 * period, without anything that names the person. Refuses while a submission is with a university unless --force.
 */
class EraseAccount extends Command
{
    protected $signature = 'smukn:erase-account {email : the student account to erase} {--force : erase even if a submission is in progress} {--dry-run}';

    protected $description = 'Erase a student account on request (documents deleted, records anonymised)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user || $user->role !== 'student') {
            $this->error('No student account with that email address.');

            return self::FAILURE;
        }
        $active = $user->applications()->whereIn('stage', [Stage::SUBMITTED, Stage::UNIVERSITY_ACKNOWLEDGED, Stage::UNIVERSITY_STAGE])->pluck('application_number');
        if ($active->isNotEmpty() && ! $this->option('force')) {
            $this->error('A submission is with a university ('.$active->implode(', ').'). Tell the student, or run with --force once it is resolved.');

            return self::FAILURE;
        }
        $applications = $user->applications()->get();
        $versions = DocumentVersion::whereNull('purged_at')->whereHas('document', fn ($q) => $q->whereIn('application_id', $applications->pluck('id')))->get();
        $this->info(sprintf('%s: %d application(s), %d document file(s).', $this->option('dry-run') ? 'Would erase' : 'Erasing', $applications->count(), $versions->count()));
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        foreach ($versions as $v) {
            Storage::disk($v->disk)->delete($v->path);
            $v->forceFill(['path' => '', 'purged_at' => now()])->save();
        }
        DB::transaction(function () use ($applications, $user) {
            foreach ($applications as $a) {
                /** @var Application $a */
                if (! $a->isTerminal()) {
                    $a->forceFill(['stage' => Stage::WITHDRAWN, 'withdrawn_at' => now()])->save();
                }
                Anonymiser::application($a);
            }
            Anonymiser::user($user->id);
            DB::table('users')->where('id', $user->id)->update(['erased_at' => now()]);
        });
        Log::warning('account.erased', ['user_id' => $user->id, 'applications' => $applications->count(), 'files' => $versions->count()]);
        $this->info('Done. The account no longer identifies the person; the student cannot sign in.');

        return self::SUCCESS;
    }
}
