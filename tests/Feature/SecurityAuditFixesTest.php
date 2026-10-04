<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

/** Regression tests for the 2026-10-04 security audit (ops/reports/security-audit-2026-10-04.md). */
class SecurityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // TrustHosts stores trusted hosts statically on the Symfony request; a production-mode test earlier in the run
        // leaves them set. Start every test from a clean slate.
        Request::setTrustedHosts([]);
    }

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    public function test_production_refuses_a_request_for_a_host_that_is_not_app_url(): void
    {
        config(['app.url' => 'https://studymedicineuknigeria.com']);
        $this->app['env'] = 'production';
        $this->get('http://evil.example/fees')->assertStatus(400);
        $this->get('https://studymedicineuknigeria.com/fees')->assertOk();
    }

    private function canonical(): void
    {
        config(['app.url' => 'https://studymedicineuknigeria.com', 'app.force_canonical_host' => true]);
    }

    public function test_a_forged_host_header_never_reaches_a_password_reset_link(): void
    {
        $this->canonical();
        Notification::fake();
        $user = User::factory()->create(['email' => 'victim@example.test']);

        $this->post('http://evil.example/password/email', ['email' => 'victim@example.test']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $url = $n->toMail($user)->actionUrl;
            $this->assertStringStartsWith('https://studymedicineuknigeria.com/password/reset/', $url);
            $this->assertStringNotContainsString('evil.example', $url);

            return true;
        });
    }

    public function test_the_front_controller_redirect_never_uses_the_request_host(): void
    {
        $this->canonical();
        $this->get('http://evil.example/index.php/fees')->assertStatus(301)->assertRedirect('https://studymedicineuknigeria.com/fees');
    }
}
