<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A new server has no admin; the owner registers, then smukn:grant-role (workflow "Grant account role") promotes them. */
class GrantRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_admin_is_granted_from_the_shell_and_must_enrol_two_step_verification(): void
    {
        $owner = User::factory()->unverified()->create(['email' => 'owner@example.test']);

        $this->artisan('smukn:grant-role', ['email' => ' Owner@Example.test ', 'role' => 'admin', '--verify-email' => true])
            ->expectsOutputToContain('student -> admin; email marked verified')->assertSuccessful();
        $owner->refresh();
        $this->assertSame('admin', $owner->role);
        $this->assertNotNull($owner->email_verified_at);
        $this->assertTrue($owner->requiresTwoFactor(), 'the admin area stays closed until an authenticator is enrolled');
        $this->actingAs($owner)->get('/admin')->assertRedirect(route('two-factor.setup'));
    }

    public function test_unknown_accounts_bad_roles_and_removing_the_last_admin_are_refused(): void
    {
        $this->artisan('smukn:grant-role', ['email' => 'nobody@example.test', 'role' => 'admin'])->expectsOutputToContain('Register on the site first')->assertFailed();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->artisan('smukn:grant-role', ['email' => $admin->email, 'role' => 'owner'])->assertFailed();
        $this->artisan('smukn:grant-role', ['email' => $admin->email, 'role' => 'student'])->expectsOutputToContain('only admin account')->assertFailed();
        $this->assertSame('admin', $admin->fresh()->role);
    }
}
