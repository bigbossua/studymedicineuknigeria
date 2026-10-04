<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The only way to create the first staff or admin account on a server: the owner registers on the site like any
 * student, then this command (run over SSH by the "Grant account role" workflow) changes that account's role.
 * A staff or admin account must enrol an authenticator at its next sign-in (EnsureTwoFactor). Demoting the last
 * admin is refused. --verify-email marks the address verified for a site that cannot send mail yet; the operator
 * running the command vouches for the address.
 */
class GrantRole extends Command
{
    protected $signature = 'smukn:grant-role {email : Address the account registered with} {role : student, staff or admin} {--verify-email : Also mark the address verified (operator vouches for it)}';

    protected $description = 'Set the role of an existing account (first admin on a new server, or staff changes from the shell)';

    public function handle(): int
    {
        $role = (string) $this->argument('role');
        if (! in_array($role, ['student', 'staff', 'admin'], true)) {
            $this->error('Role must be student, staff or admin.');

            return self::FAILURE;
        }
        $user = User::where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();
        if (! $user) {
            $this->error('No account with that email. Register on the site first, then run this again.');

            return self::FAILURE;
        }
        if ($user->role === 'admin' && $role !== 'admin' && User::where('role', 'admin')->count() === 1) {
            $this->error('This is the only admin account; grant another admin first.');

            return self::FAILURE;
        }
        $before = ['role' => $user->role, 'email_verified' => $user->email_verified_at !== null];
        $user->forceFill(['role' => $role]);
        if ($this->option('verify-email') && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();
        $after = ['role' => $user->role, 'email_verified' => $user->email_verified_at !== null];
        // Console actions have no signed-in admin; like smukn:two-factor-reset they are recorded in the application log (warning level).
        Log::warning('account.role_granted_by_console', ['user_id' => $user->id, 'before' => $before, 'after' => $after]);
        $this->info("{$user->email}: {$before['role']} -> {$user->role}".($after['email_verified'] && ! $before['email_verified'] ? '; email marked verified' : '').
            (in_array($role, ['staff', 'admin'], true) ? '. Two-step verification is required at the next sign-in.' : '.'));

        return self::SUCCESS;
    }
}
