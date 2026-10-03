<?php

namespace App\Console\Commands;

use App\Models\ReferenceFact;
use Illuminate\Console\Command;

/** Nightly: verified facts past review_due_at become REVIEW_DUE so stale fees/deadlines never look current (brief 52–53). */
class FlagReviewDueFacts extends Command
{
    protected $signature = 'smukn:flag-review-due';

    protected $description = 'Flag verified reference facts whose review date has passed';

    public function handle(): int
    {
        $n = ReferenceFact::where('verification_status', ReferenceFact::VERIFIED)->whereNotNull('review_due_at')->where('review_due_at', '<', now()->toDateString())->update(['verification_status' => ReferenceFact::REVIEW_DUE]);
        $this->info("$n fact(s) flagged for review.");

        return self::SUCCESS;
    }
}
