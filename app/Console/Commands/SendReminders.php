<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Enums\Stage;
use App\Models\Application;
use App\Notifications\ApplicationNotification;
use Illuminate\Console\Command;

/**
 * Reminder cadence (docs/architecture/12.8): incomplete after 2, 7, 14, 30 days of inactivity; documents requested after 3, 10 days;
 * approval requested after 2, 7 days. Max one reminder email per application per 48 hours. Stops on terminal/hold stages.
 */
class SendReminders extends Command
{
    protected $signature = 'smukn:reminders {--dry}';
    protected $description = 'Send due reminder emails for inactive applications';

    public function handle(): int
    {
        $sent = 0;
        $apps = Application::with('documents', 'reminders', 'user', 'tier', 'payments.tierPrice', 'submissions', 'authorisations')->whereNull('withdrawn_at')->whereNull('closed_at')->get();
        foreach ($apps as $a) {
            if (in_array($a->stage, [Stage::ON_HOLD, Stage::COMPLETED, Stage::SUBMITTED, Stage::UNIVERSITY_ACKNOWLEDGED, Stage::UNIVERSITY_STAGE], true)) continue;
            $last = $a->reminders->whereNotNull('sent_at')->max('sent_at');
            if ($last && $last->gt(now()->subHours(48))) continue;
            $idle = $a->last_activity_at ?? $a->created_at;
            $days = (int) $idle->diffInDays(now());
            $type = null;
            if ($a->stage === Stage::READY_FOR_STUDENT_APPROVAL) { $sinceReq = optional($a->events()->where('type', 'approval.requested')->latest('created_at')->first())->created_at; $d = $sinceReq ? (int) $sinceReq->diffInDays(now()) : 0; if (in_array($d, [2, 7], true)) $type = 'approval'; }
            elseif ($a->documents->contains(fn ($d) => $d->status->needsStudent() && $d->requested_by)) { $req = $a->documents->filter(fn ($d) => $d->status->needsStudent() && $d->requested_by)->min('updated_at'); $d = (int) $req->diffInDays(now()); if (in_array($d, [3, 10], true) || ($d > 10 && $d % 7 === 0)) $type = 'document'; }
            elseif (in_array($a->stage, [Stage::APPLICATION_STARTED, Stage::APPLICATION_INCOMPLETE, Stage::DOCUMENTS_INCOMPLETE, Stage::PAYMENT_REQUIRED], true) && in_array($days, [2, 7, 14, 30], true)) $type = 'incomplete';
            if (! $type) continue;
            if ($a->reminders->contains(fn ($r) => $r->type === $type.':'.$days && $r->sent_at)) continue;
            [$label] = app(\App\Services\Applications\StageResolver::class)->nextAction($a);
            if (! $this->option('dry')) {
                $a->user->notify(new ApplicationNotification($a, 'reminder', ['next' => 'Next step: '.$label]));
                $a->reminders()->create(['type' => $type.':'.$days, 'scheduled_for' => now(), 'sent_at' => now()]);
            }
            $this->line("reminder {$type} → {$a->application_number} ({$label})");
            $sent++;
        }
        $this->info("$sent reminder(s) ".($this->option('dry') ? 'would be ' : '').'sent.');

        return self::SUCCESS;
    }
}
