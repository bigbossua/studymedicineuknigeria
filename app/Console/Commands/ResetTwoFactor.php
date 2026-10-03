<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Lock-out recovery for staff who lost their authenticator and recovery codes.
 * Runs only from the server shell; the user re-enrols at next sign-in.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'smukn:two-factor-reset {email : Account email address}';

    protected $description = 'Remove the authenticator enrolment from one account (server-side lock-out recovery)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No account with that email.');

            return self::FAILURE;
        }
        if (! $user->hasTwoFactorEnabled()) {
            $this->info('Two-step verification is not enabled on this account.');

            return self::SUCCESS;
        }
        if (! $this->confirm("Remove two-step verification from {$user->email} ({$user->role})?")) {
            return self::SUCCESS;
        }
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        Log::warning('two_factor.reset_by_console', ['user_id' => $user->id, 'role' => $user->role]);
        $this->info('Done. The account must enrol again at next sign-in.');

        return self::SUCCESS;
    }
}
