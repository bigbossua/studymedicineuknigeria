<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class ExpireStalePayments extends Command
{
    protected $signature = 'smukn:expire-payments';
    protected $description = 'Mark Stripe checkout sessions initiated more than 24 hours ago as expired';

    public function handle(): int
    {
        $n = Payment::where('status', 'INITIATED')->where('created_at', '<', now()->subDay())->update(['status' => 'EXPIRED']);
        $this->info("$n payment(s) expired.");

        return self::SUCCESS;
    }
}
