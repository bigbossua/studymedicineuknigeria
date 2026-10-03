<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The account gates everything else: registration → verification → portal, and the full password-reset loop. */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_verification_link_that_opens_the_portal(): void
    {
        Notification::fake();
        $this->post('/register', ['name' => 'Ngozi Eze', 'email' => 'ngozi@example.test', 'password' => 'Longpass12345', 'password_confirmation' => 'Longpass12345', 'terms' => 1])->assertRedirect('/email/verify');
        $user = User::where('email', 'ngozi@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->get('/portal')->assertRedirect('/email/verify');

        $link = null;
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $n) use ($user, &$link) {
            $link = $n->toMail($user)->actionUrl;

            return true;
        });
        $this->assertNotNull($link);
        $this->get($link)->assertRedirect('/portal');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/portal')->assertOk();
    }

    public function test_password_reset_loop_works_end_to_end_without_revealing_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $this->post('/password/email', ['email' => 'nobody@example.test'])->assertSessionHas('status');
        $this->post('/password/email', ['email' => 'reset@example.test'])->assertSessionHas('status');

        $link = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user, &$link) {
            $link = $n->toMail($user)->actionUrl;

            return true;
        });
        $this->assertNotNull($link);
        $this->get($link)->assertOk()->assertSee('name="token"', false);
        parse_str(parse_url($link, PHP_URL_QUERY) ?? '', $q);
        $token = basename(parse_url($link, PHP_URL_PATH));

        $this->post('/password/reset', ['token' => 'wrong', 'email' => 'reset@example.test', 'password' => 'Newpass123456', 'password_confirmation' => 'Newpass123456'])->assertSessionHasErrors('email');
        $this->post('/password/reset', ['token' => $token, 'email' => 'reset@example.test', 'password' => 'Newpass123456', 'password_confirmation' => 'Newpass123456'])->assertRedirect('/login');
        $this->post('/login', ['email' => 'reset@example.test', 'password' => 'Newpass123456'])->assertRedirect('/portal');
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => 'reset@example.test', 'password' => 'password'])->assertSessionHasErrors('email');
    }
}
