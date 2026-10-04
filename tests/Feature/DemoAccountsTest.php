<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_seed_locally_with_a_two_step_admin_and_one_application(): void
    {
        $this->seed(PlatformSeeder::class);
        $this->seed(DemoAccountsSeeder::class);
        $this->seed(DemoAccountsSeeder::class); // idempotent

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($admin->isStaff());
        $this->assertTrue($admin->hasTwoFactorEnabled());
        $student = User::where('email', 'student@example.test')->firstOrFail();
        $this->assertNotNull($student->email_verified_at);
        $this->assertSame(1, $student->applications()->count());
        $this->assertMatchesRegularExpression('/^SMUKN-\d{4}-\d{6}$/', $student->applications()->value('application_number'));
    }

    public function test_demo_accounts_are_never_seeded_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        try {
            (new DemoAccountsSeeder)->run();
            $this->fail('the seeder ran in production');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('only seeded in the local or testing environment', $e->getMessage());
        }
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }
}
