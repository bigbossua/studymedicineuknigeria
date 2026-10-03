<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_matches_the_rfc_6238_test_vectors(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $secret);
        $this->assertSame('287082', Totp::code($secret, 59));
        $this->assertSame('081804', Totp::code($secret, 1111111109));
        $this->assertSame('005924', Totp::code($secret, 1234567890));
        $this->assertSame('12345678901234567890', Totp::base32Decode($secret));
        $this->assertNull(Totp::matchingCounter($secret, '000000', 1, 59));
        $this->assertSame(Totp::counter(59), Totp::matchingCounter($secret, '287 082', 1, 59 + 30));
    }

    public function test_staff_without_an_authenticator_are_sent_to_setup_and_students_are_not(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get('/admin')->assertRedirect('/two-factor/setup');
        $this->actingAs($staff)->get('/portal')->assertRedirect('/two-factor/setup');

        $student = User::factory()->create();
        $this->actingAs($student)->get('/portal')->assertOk();
    }

    public function test_enrolment_confirms_a_code_shows_recovery_codes_once_and_unlocks_the_admin_area(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get('/two-factor/setup')->assertOk()->assertSee('Setup key');
        $secret = session('two_factor.pending_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($staff)->withSession(['two_factor.pending_secret' => $secret])->post('/two-factor/setup', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertNull($staff->fresh()->two_factor_confirmed_at);

        $response = $this->actingAs($staff)->withSession(['two_factor.pending_secret' => $secret])->post('/two-factor/setup', ['code' => Totp::code($secret)]);
        $response->assertRedirect('/two-factor/recovery-codes');
        $staff->refresh();
        $this->assertTrue($staff->hasTwoFactorEnabled());
        $this->assertSame($secret, $staff->two_factor_secret);
        $this->assertCount(8, $staff->two_factor_recovery_codes);
        $this->assertDatabaseHas('admin_actions', ['admin_user_id' => $staff->id, 'action' => 'two_factor.enabled']);

        $codes = session('two_factor.fresh_codes');
        $this->assertCount(8, $codes);
        $this->get('/two-factor/recovery-codes')->assertOk()->assertSee($codes[0]);
        $this->get('/two-factor/recovery-codes')->assertRedirect('/admin'); // shown once only
        $this->get('/admin')->assertOk();
    }

    public function test_every_session_must_pass_the_challenge_and_codes_cannot_be_replayed(): void
    {
        $staff = $this->enrolled();
        $this->actingAs($staff)->get('/admin/users')->assertRedirect('/two-factor/challenge');
        $this->actingAs($staff)->post('/two-factor/challenge', ['code' => '123456'])->assertSessionHasErrors('code');

        $code = Totp::code($staff->two_factor_secret);
        $this->actingAs($staff)->withSession(['url.intended' => '/admin/users'])->post('/two-factor/challenge', ['code' => $code])->assertRedirect('/admin/users');
        $this->get('/admin/users')->assertOk();

        // the same code in a fresh session is refused
        $this->actingAs($staff)->flushSession();
        $this->actingAs($staff)->post('/two-factor/challenge', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_a_recovery_code_signs_in_once_only(): void
    {
        $staff = $this->enrolled(['ABCDE-FGHJK', 'LMNPQ-RSTUV']);
        $this->actingAs($staff)->post('/two-factor/challenge', ['code' => 'abcde-fghjk'])->assertRedirect('/admin');
        $this->assertCount(1, $staff->fresh()->two_factor_recovery_codes);

        $this->actingAs($staff)->flushSession();
        $this->actingAs($staff)->post('/two-factor/challenge', ['code' => 'ABCDE-FGHJK'])->assertSessionHasErrors('code');
    }

    public function test_students_may_switch_it_off_with_their_password_but_staff_may_not(): void
    {
        $student = $this->enrolled(role: 'student');
        $this->actingAs($student)->withSession([EnsureTwoFactor::SESSION_KEY => $student->id])->post('/two-factor/disable', ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->actingAs($student)->withSession([EnsureTwoFactor::SESSION_KEY => $student->id])->post('/two-factor/disable', ['current_password' => 'password'])->assertRedirect('/portal/profile');
        $this->assertFalse($student->fresh()->hasTwoFactorEnabled());

        $staff = $this->enrolled();
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->post('/two-factor/disable', ['current_password' => 'password'])->assertForbidden();
        $this->assertTrue($staff->fresh()->hasTwoFactorEnabled());
    }

    public function test_an_admin_can_reset_another_account_but_staff_and_self_cannot(): void
    {
        $admin = $this->enrolled(role: 'admin');
        $other = $this->enrolled();
        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->post("/admin/users/{$other->id}/two-factor/reset")->assertSessionHas('status');
        $this->assertFalse($other->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('admin_actions', ['admin_user_id' => $admin->id, 'action' => 'two_factor.reset', 'target_id' => $other->id]);

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->post("/admin/users/{$admin->id}/two-factor/reset")->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $staff = $this->enrolled();
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->post("/admin/users/{$admin->id}/two-factor/reset")->assertForbidden();
    }

    public function test_signing_in_again_requires_the_code_again(): void
    {
        $staff = $this->enrolled();
        $this->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertNull(session(EnsureTwoFactor::SESSION_KEY));
        $this->get('/admin')->assertRedirect('/two-factor/challenge');
    }

    private function staff(string $role = 'staff'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function enrolled(array $recovery = [], string $role = 'staff'): User
    {
        $user = $this->staff($role);
        $user->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $recovery),
        ])->save();

        return $user;
    }
}
