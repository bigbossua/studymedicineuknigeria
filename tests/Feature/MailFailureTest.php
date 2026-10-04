<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\QueuedResetPassword;
use App\Notifications\Auth\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** A mail outage, or a staging site without mail settings, must never turn registration or a reset into an error page. */
class MailFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_password_reset_survive_an_unreachable_mail_server(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 9, 'mail.mailers.smtp.timeout' => 2]);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'Longpass12345', 'password_confirmation' => 'Longpass12345', 'terms' => '1'])
            ->assertRedirect(route('verification.notice'));
        $this->post('/logout');
        $this->post('/password/email', ['email' => 'ada@example.test'])->assertRedirect();
        $this->assertSame(2, DB::table('jobs')->count(), 'both emails wait in the queue and are retried, not lost');
    }

    public function test_the_auth_emails_are_queued_versions_of_laravels_own(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();
        $user->sendPasswordResetNotification('token');
        Notification::assertSentTo($user, QueuedVerifyEmail::class);
        Notification::assertSentTo($user, QueuedResetPassword::class);
    }
}
