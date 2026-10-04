<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ServiceTier;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Notifications\StaffNotification;
use Database\Seeders\PlatformSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every transactional email must render to HTML without errors and carry the application number and a plain disclaimer. */
class NotificationRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_application_notification_type_renders(): void
    {
        $this->seed(PlatformSeeder::class);
        $user = User::factory()->create(['name' => 'Adaeze Okonkwo']);
        $tier = ServiceTier::where('code', 'T2')->first();
        $this->actingAs($user)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028])->assertRedirect();
        $a = Application::first();

        $cases = [
            'application.started' => [],
            'document.requested' => ['title' => 'International passport', 'reason' => 'Photo page unreadable'],
            'document.accepted' => ['title' => 'WAEC certificate'],
            'document.rejected' => ['title' => 'WAEC certificate', 'reason' => 'Scan is cropped'],
            'documents.complete' => [],
            'services.approved' => ['note' => 'Your profile looks complete.'],
            'payment.succeeded' => ['amount' => '£250.00'],
            'approval.requested' => [],
            'student.approved' => ['at' => '3 October 2026, 14:00'],
            'submission.sent' => ['university' => 'University of Leicester', 'course' => 'Medicine MBChB A100'],
            'university.response' => ['university' => 'University of Leicester', 'status' => 'Interview', 'note' => 'Online MMI in January'],
            'action.required' => ['note' => 'Please confirm your intake year.'],
            'message.received' => [],
            'reminder' => ['next' => 'Next step: upload your passport'],
            'something.else' => ['note' => 'Generic update'],
        ];
        foreach ($cases as $type => $data) {
            $mail = (new ApplicationNotification($a, $type, $data))->toMail($user);
            $html = (string) $mail->render();
            $this->assertStringContainsString($a->application_number, $mail->subject, "$type subject carries the application number");
            $this->assertStringContainsString('Dear Adaeze,', $html, $type);
            $this->assertStringContainsString('Admission decisions are made solely by universities', $html, $type);
            $this->assertStringContainsString('email-header@2x.png', $html, "$type carries the brand header");
            $this->assertMatchesRegularExpression('#href="http[^"]+/portal#', $html, "$type has a portal action link");
        }
    }

    public function test_staff_and_framework_notifications_render(): void
    {
        $user = User::factory()->create(['name' => 'Site Admin']);
        $staff = (string) (new StaffNotification('New lead: Ada', ['ada@example.test · T1'], 'http://localhost/admin/leads'))->toMail($user)->render();
        $this->assertStringContainsString('[SMUKN] New lead: Ada', (new StaffNotification('New lead: Ada', []))->toMail($user)->subject);
        $this->assertStringContainsString('Open in admin', $staff);

        $this->assertStringContainsString('Verify Email Address', (string) (new VerifyEmail)->toMail($user)->render());
        $this->assertStringContainsString('Reset Password', (string) (new ResetPassword('token-123'))->toMail($user)->render());
    }
}
