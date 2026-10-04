<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Notifications\ApplicationNotification;
use App\Services\Payments\StripeService;
use Illuminate\Console\Command;

/**
 * Keeps the promise on the confirmation page: once a way to pay is open (Stripe configured, or bank transfer switched
 * on), every approved student who chose a service and has not paid is emailed once. Run by ops/update-env.sh after
 * payment settings are written; safe to repeat.
 */
class NotifyPaymentsOpen extends Command
{
    protected $signature = 'smukn:payments-open-notify';

    protected $description = 'Email approved students waiting to pay, once, when payment opens';

    public function handle(StripeService $stripe): int
    {
        if (! $stripe->paymentsOpen()) {
            $this->info('Payment is not open yet (no usable Stripe key, bank transfer off): nobody notified.');

            return self::SUCCESS;
        }
        $sent = 0;
        $waiting = Application::with('user', 'payments')->whereNotNull('services_approved_at')->whereNotNull('service_tier_id')
            ->whereNull('withdrawn_at')->whereNull('closed_at')->whereDoesntHave('events', fn ($q) => $q->where('type', 'payments.open_notified'))->get();
        foreach ($waiting as $a) {
            if ($a->payments->whereIn('status', ['SUCCEEDED', 'MANUAL_REVIEW'])->isNotEmpty()) {
                continue;
            }
            $a->user->notify(new ApplicationNotification($a, 'payments.open'));
            $a->record('payments.open_notified');
            $sent++;
        }
        $this->info("Students told that payment is open: {$sent}.");

        return self::SUCCESS;
    }
}
