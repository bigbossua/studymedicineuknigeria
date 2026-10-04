<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\User;
use App\Services\Applications\ChecklistBuilder;
use App\Services\Applications\StageResolver;
use App\Support\Totp;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo accounts for local previews, Codespaces and the browser QA scripts (ops/qa): a verified student with one
 * application and a staff admin with two-step verification enrolled. Never runs in production or staging: the guard
 * throws, and DatabaseSeeder only calls it in the local environment. Idempotent; the admin's TOTP secret is random per
 * database and is read with `php artisan tinker` (CLAUDE.md).
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo accounts are only seeded in the local or testing environment.');
        }

        $student = User::firstOrNew(['email' => 'student@example.test']);
        $student->fill(['name' => 'Demo Student', 'password' => 'Testpass12345', 'country' => 'NG']);
        $student->forceFill(['role' => 'student', 'email_verified_at' => $student->email_verified_at ?? now()])->save();

        $admin = User::firstOrNew(['email' => 'admin@example.test']);
        $admin->fill(['name' => 'Demo Admin', 'password' => 'Adminpass12345']);
        $admin->forceFill(['role' => 'admin', 'email_verified_at' => $admin->email_verified_at ?? now()]);
        if (! $admin->hasTwoFactorEnabled()) {
            $admin->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()]);
        }
        $admin->save();

        if (! $student->applications()->exists()) {
            // like a real new student: no service yet; the demo admin approves the profile to show the service choice
            $year = now()->year + 2;
            $application = Application::create([
                'application_number' => Application::nextNumber($year), 'user_id' => $student->id, 'service_tier_id' => null,
                'intake_year' => $year, 'form' => ['study' => ['intake_year' => $year]], 'section_status' => [], 'last_activity_at' => now(),
            ]);
            $application->record('application.started', ['demo' => true], $student->id);
            app(ChecklistBuilder::class)->refresh($application);
            app(StageResolver::class)->sync($application);
        }
    }
}
