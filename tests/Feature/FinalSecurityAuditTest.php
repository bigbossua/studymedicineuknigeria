<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\Stage;
use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\Authorisation;
use App\Models\Submission;
use App\Models\University;
use App\Models\User;
use App\Services\Applications\FormSteps;
use App\Services\Applications\StageResolver;
use App\Services\Documents\DocumentStore;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tests\Support\FakeStripeClient;
use Tests\TestCase;

/**
 * Final security audit (2026-10-06): the gaps left by DocumentSecurityTest, PortalPaymentsAndDocumentsTest,
 * StripeWebhookTest, ServicePaymentsTest, SecurityAuditFixesTest, TwoFactorTest and ApplicationWorkflowTest.
 * Deterministic, no network: Stripe is the FakeStripeClient recorder and webhook events are signed locally.
 */
class FinalSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private const WHSEC = 'whsec_final_audit';

    private User $alice;

    private User $bob;

    private Application $a;

    private Application $b;

    protected function setUp(): void
    {
        parent::setUp();
        SymfonyRequest::setTrustedHosts([]);
        Notification::fake();
        Storage::fake('private');
        $this->seed(PlatformSeeder::class);
        [$this->alice, $this->bob] = [User::factory()->create(), User::factory()->create()];
        $this->a = $this->startFor($this->alice);
        $this->b = $this->startFor($this->bob);
    }

    protected function tearDown(): void
    {
        SymfonyRequest::setTrustedHosts([]);
        parent::tearDown();
    }

    private function startFor(User $user): Application
    {
        $this->actingAs($user)->post('/portal/start', ['intake_year' => 2028])->assertRedirect();

        return Application::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function doc(Application $app, string $code = 'PASSPORT')
    {
        return $app->documents()->where('code', $code)->firstOrFail();
    }

    private function pdf(): string
    {
        return file_get_contents(base_path('ops/qa/fixtures/dummy-test-document.pdf'));
    }

    private function upload(User $user, Application $app, $doc, string $name, string $bytes)
    {
        return $this->actingAs($user)->post("/portal/{$app->application_number}/documents/{$doc->id}", ['file' => UploadedFile::fake()->createWithContent($name, $bytes)]);
    }

    private function staff(string $role = 'staff', bool $enrolled = true): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role] + ($enrolled ? ['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()] : []))->save();

        return $u;
    }

    private function asStaff(User $u): static
    {
        return $this->actingAs($u)->withSession([EnsureTwoFactor::SESSION_KEY => $u->id]);
    }

    private function jpeg(int $w = 800, int $h = 800): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 200, 120, 40));
        ob_start();
        imagejpeg($img, null, 80);
        imagedestroy($img);

        return ob_get_clean();
    }

    private function zip(array $entries): string
    {
        $path = storage_path('framework/testing/audit-'.uniqid().'.zip');
        @mkdir(dirname($path), 0777, true);
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $bytes = file_get_contents($path);
        @unlink($path);

        return $bytes;
    }

    // ---------------------------------------------------------------- Documents

    public function test_forged_non_existent_and_mismatched_document_ids_never_reach_a_file(): void
    {
        $passport = $this->doc($this->a);
        $this->upload($this->alice, $this->a, $passport, 'p.pdf', $this->pdf())->assertSessionHasNoErrors();
        $v = $passport->fresh()->versions()->firstOrFail();
        $statement = $this->doc($this->a, 'STATEMENT');
        $n = $this->a->application_number;

        // non-existent or malformed ids answer 404, never another file
        $this->actingAs($this->alice)->get("/portal/{$n}/documents/999999/v/{$v->id}")->assertNotFound();
        $this->actingAs($this->alice)->get("/portal/{$n}/documents/{$passport->id}/v/999999")->assertNotFound();
        $this->actingAs($this->alice)->get("/portal/{$n}/documents/abc/v/{$v->id}")->assertNotFound();
        $this->actingAs($this->alice)->get("/portal/SMUKN-2028-000000/documents/{$passport->id}/v/{$v->id}")->assertNotFound();
        // a real version paired with another document of the same application is refused
        $this->actingAs($this->alice)->get("/portal/{$n}/documents/{$statement->id}/v/{$v->id}")->assertForbidden();

        // Bob, through his own application number, cannot open, read or upload into Alice's document
        $bn = $this->b->application_number;
        $this->actingAs($this->bob)->get("/portal/{$bn}/documents/{$passport->id}")->assertForbidden();
        $this->upload($this->bob, $this->b, $passport, 'p.pdf', $this->pdf())->assertForbidden();
        $this->actingAs($this->bob)->get("/portal/{$n}/documents")->assertForbidden();
        $this->assertSame(1, $passport->fresh()->versions()->count());

        // staff (two-step passed) also cannot pair a version with the wrong document
        $staff = $this->staff();
        $this->asStaff($staff)->get("/portal/{$n}/documents/{$statement->id}/v/{$v->id}")->assertForbidden();
        $this->asStaff($staff)->get("/portal/{$bn}/documents/{$passport->id}/v/{$v->id}")->assertForbidden();
    }

    public function test_executables_and_php_are_refused_and_polyglots_are_neutralised(): void
    {
        $doc = $this->doc($this->a);
        foreach ([
            'passport.pdf' => '<?php system($_GET["c"]); ?>',
            'passport.jpg' => "MZ\x90\x00\x03\x00\x00\x00".str_repeat("\x00", 300),
            'passport.png' => "\x7fELF\x02\x01\x01".str_repeat("\x00", 300),
            'passport.php.pdf' => "#!/bin/sh\nrm -rf /\n",
        ] as $name => $bytes) {
            $this->upload($this->alice, $this->a, $doc, $name, $bytes)->assertSessionHasErrors('file');
        }
        $this->assertSame(0, $doc->fresh()->versions()->count());

        // a real JPEG carrying PHP is re-encoded: the payload does not survive
        $this->upload($this->alice, $this->a, $doc, 'photo.jpg', $this->jpeg().'<?php system($_GET["c"]); ?>')->assertSessionHasNoErrors();
        $v = $doc->fresh()->versions()->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('<?php', app(DocumentStore::class)->contents($v));
        $this->assertStringEndsWith('.jpg.enc', $v->path);

        // a PDF/PHP polyglot is inert: stored under a .pdf name on the private disk, served only as an attachment
        $poly = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n<?php system(\$_GET['c']); ?>\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $this->upload($this->alice, $this->a, $doc, 'shell.php', $poly);
        $v = $doc->fresh()->versions()->latest('id')->firstOrFail();
        $this->assertDoesNotMatchRegularExpression('/\.php/i', $v->path);
        $this->assertDoesNotMatchRegularExpression('/\.php/i', $v->safeFilename());
        $r = $this->actingAs($this->alice)->get("/portal/{$this->a->application_number}/documents/{$doc->id}/v/{$v->id}")->assertOk();
        $this->assertStringStartsWith('attachment', (string) $r->headers->get('Content-Disposition'));
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertContains($r->headers->get('Content-Type'), ['application/pdf', 'image/jpeg']);
    }

    public function test_a_malformed_or_truncated_pdf_is_refused(): void
    {
        $doc = $this->doc($this->a);
        // a PDF header followed by garbage: no objects, no cross-reference table, no end-of-file marker
        $this->upload($this->alice, $this->a, $doc, 'garbage.pdf', "%PDF-1.7\n".str_repeat("\x00\xff\x13garbage", 200))->assertSessionHasErrors('file');
        // a real PDF cut off half-way through the upload
        $this->upload($this->alice, $this->a, $doc, 'truncated.pdf', substr($this->pdf(), 0, 300))->assertSessionHasErrors('file');
        $this->assertSame(0, $doc->fresh()->versions()->count());
    }

    public function test_macro_enabled_office_files_are_refused_in_every_disguise(): void
    {
        $doc = $this->doc($this->a, 'STATEMENT'); // accepts pdf and docx
        $ct = fn (string $main) => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="'.$main.'"/></Types>';
        $docx = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml';

        $cases = [
            // a .docm as itself
            'statement.docm' => $this->zip(['[Content_Types].xml' => $ct('application/vnd.ms-word.document.macroEnabled.main+xml'), '_rels/.rels' => '<Relationships/>', 'word/document.xml' => '<w:document/>', 'word/vbaProject.bin' => str_repeat('A', 64)]),
            // VBA payload hidden behind ordinary .docx content types
            'statement.docx' => $this->zip(['[Content_Types].xml' => $ct($docx), '_rels/.rels' => '<Relationships/>', 'word/document.xml' => '<w:document/>', 'word/vbaProject.bin' => str_repeat('A', 64)]),
            // an Excel macro workbook, as .xlsm and renamed to .docx
            'budget.xlsm' => $this->zip(['[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/xl/workbook.xml" ContentType="application/vnd.ms-excel.sheet.macroEnabled.main+xml"/></Types>', '_rels/.rels' => '<Relationships/>', 'xl/workbook.xml' => '<workbook/>', 'xl/vbaProject.bin' => str_repeat('B', 64)]),
        ];
        $cases['budget-renamed.docx'] = $cases['budget.xlsm'];
        foreach ($cases as $name => $bytes) {
            $this->upload($this->alice, $this->a, $doc, $name, $bytes)->assertSessionHasErrors('file');
        }
        $this->assertSame(0, $doc->fresh()->versions()->count());
    }

    public function test_documents_live_only_on_the_private_disk_and_sensitive_types_are_encrypted(): void
    {
        $passport = $this->doc($this->a);
        $statement = $this->doc($this->a, 'STATEMENT');
        $this->upload($this->alice, $this->a, $passport, 'p.pdf', $this->pdf())->assertSessionHasNoErrors();
        $this->upload($this->alice, $this->a, $statement, 's.pdf', $this->pdf())->assertSessionHasNoErrors();
        $statement->forceFill(['code' => 'FINANCIAL'])->save(); // reuse the row as a proof-of-funds document
        $this->upload($this->alice, $this->a, $statement->fresh(), 'f.pdf', $this->pdf())->assertSessionHasNoErrors();

        foreach (DB::table('document_versions')->get() as $v) {
            $this->assertSame('private', $v->disk);
            $this->assertStringStartsWith('applications/'.$this->a->application_number.'/', $v->path);
            $this->assertTrue(Storage::disk('private')->exists($v->path));
            // no unsigned request serves the stored path
            $this->get('/'.$v->path)->assertNotFound();
            $this->assertContains($this->get('/storage/'.$v->path)->getStatusCode(), [403, 404]);
        }
        // PASSPORT and FINANCIAL carry application-level encryption (docs/architecture/14-document-architecture.md step 6)
        $this->assertSame(3, DB::table('document_versions')->count());
        foreach (DB::table('document_versions')->get() as $v) {
            if (str_contains($v->path, '/PASSPORT/') || str_contains($v->path, '/FINANCIAL/')) {
                $this->assertTrue((bool) $v->encrypted, $v->path);
                $this->assertStringNotContainsString('%PDF', Storage::disk('private')->get($v->path));
            }
        }
        $this->assertStringStartsNotWith(public_path(), (string) config('filesystems.disks.private.root'));
    }

    public function test_the_document_directory_is_not_exposed_through_the_frameworks_served_disk(): void
    {
        // Laravel registers GET and PUT /storage/{path} for every local disk with 'serve' => true; those routes answer a
        // signed URL with the raw file, bypassing ownership checks, the access log and decryption-on-download.
        $docsRoot = realpath(config('filesystems.disks.private.root')) ?: config('filesystems.disks.private.root');
        foreach (config('filesystems.disks') as $name => $disk) {
            if (($disk['driver'] ?? null) !== 'local' || ! ($disk['serve'] ?? false)) {
                continue;
            }
            $root = realpath($disk['root']) ?: $disk['root'];
            $this->assertFalse(str_starts_with($root, $docsRoot) || str_starts_with($docsRoot, $root), "disk '{$name}' serves {$root}, which contains the student document store");
        }
    }

    public function test_uploads_and_downloads_are_written_to_the_audit_trail(): void
    {
        $doc = $this->doc($this->a);
        $this->upload($this->alice, $this->a, $doc, 'p.pdf', $this->pdf())->assertSessionHasNoErrors();
        $v = $doc->fresh()->versions()->firstOrFail();
        $this->assertDatabaseHas('application_events', ['application_id' => $this->a->id, 'type' => 'document.uploaded', 'actor_user_id' => $this->alice->id]);
        $this->assertDatabaseHas('document_events', ['document_id' => $doc->id, 'to_status' => DocumentStatus::UNDER_REVIEW->value, 'actor_user_id' => $this->alice->id]);
        $this->assertSame($this->alice->id, (int) $v->uploaded_by);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->actingAs($this->alice)
            ->get("/portal/{$this->a->application_number}/documents/{$doc->id}/v/{$v->id}")->assertOk();
        $this->assertDatabaseHas('document_access_log', ['document_version_id' => $v->id, 'user_id' => $this->alice->id, 'purpose' => 'download', 'ip' => '203.0.113.7']);

        // a refused attempt leaves no "download" behind for the intruder
        $this->actingAs($this->bob)->get("/portal/{$this->a->application_number}/documents/{$doc->id}/v/{$v->id}")->assertForbidden();
        $this->assertDatabaseMissing('document_access_log', ['user_id' => $this->bob->id]);

        $staff = $this->staff();
        $this->asStaff($staff)->get("/portal/{$this->a->application_number}/documents/{$doc->id}/v/{$v->id}")->assertOk();
        $this->assertDatabaseHas('document_access_log', ['document_version_id' => $v->id, 'user_id' => $staff->id, 'purpose' => 'download']);
    }

    public function test_a_replacement_adds_a_version_and_keeps_the_previous_one(): void
    {
        $doc = $this->doc($this->a);
        $n = $this->a->application_number;
        $this->upload($this->alice, $this->a, $doc, 'first.pdf', $this->pdf())->assertSessionHasNoErrors();
        $plain = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $this->upload($this->alice, $this->a, $doc, 'second.pdf', $plain)->assertSessionHasNoErrors();

        $versions = $doc->fresh()->versions()->get()->sortBy('version')->values();
        $this->assertSame([1, 2], $versions->pluck('version')->map(fn ($x) => (int) $x)->all());
        $this->assertSame($versions[1]->id, (int) $doc->fresh()->current_version_id, 'the new upload is the current version');
        $this->assertNotSame($versions[0]->path, $versions[1]->path);
        $this->assertNotSame($versions[0]->sha256, $versions[1]->sha256);
        foreach ($versions as $v) {
            $this->assertTrue(Storage::disk('private')->exists($v->path), 'previous versions are kept (docs 14: kept until completion + 90 days)');
            $this->actingAs($this->alice)->get("/portal/{$n}/documents/{$doc->id}/v/{$v->id}")->assertOk();
        }
        $this->assertSame(DocumentStatus::UNDER_REVIEW, $doc->fresh()->status);
    }

    public function test_every_document_route_for_staff_needs_an_enrolled_and_passed_authenticator(): void
    {
        $doc = $this->doc($this->a);
        $this->upload($this->alice, $this->a, $doc, 'p.pdf', $this->pdf())->assertSessionHasNoErrors();
        $v = $doc->fresh()->versions()->firstOrFail();
        $portal = "/portal/{$this->a->application_number}/documents/{$doc->id}/v/{$v->id}";
        $admin = route('admin.applications.document', [$this->a, $doc]);

        // staff without an authenticator: sent to set one up, never the file
        $unenrolled = $this->staff('staff', false);
        $this->actingAs($unenrolled)->get($portal)->assertRedirect(route('two-factor.setup'));
        $this->actingAs($unenrolled)->get($admin)->assertRedirect(route('two-factor.setup'));

        // enrolled but not passed in this session: sent to the challenge
        $enrolled = $this->staff('staff');
        $this->actingAs($enrolled)->get($portal)->assertRedirect(route('two-factor.challenge'));
        $this->actingAs($enrolled)->get($admin)->assertRedirect(route('two-factor.challenge'));

        // passed: staff and admins can view
        $this->asStaff($enrolled)->get($portal)->assertOk();
        $this->asStaff($enrolled)->get($admin)->assertOk();
        $this->asStaff($this->staff('admin'))->get($admin)->assertOk();

        // a student with two-step verification passed is still not staff
        $this->alice->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->asStaff($this->alice)->get($admin)->assertForbidden();
        $this->assertSame(0, DB::table('document_access_log')->whereIn('user_id', [$unenrolled->id, $this->alice->id])->where('purpose', 'preview')->count());
        $this->assertSame(0, DB::table('document_access_log')->where('user_id', $unenrolled->id)->count());
    }

    // ---------------------------------------------------------------- Auth and session

    /** Evaluate config/session.php with SESSION_SECURE_COOKIE set to $value (null: absent). */
    private function sessionConfig(?string $value): array
    {
        $key = 'SESSION_SECURE_COOKIE';
        $saved = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        try {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $_SERVER[$key] = $value;
            }

            return require config_path('session.php');
        } finally {
            $saved[0] === false ? putenv($key) : putenv("{$key}={$saved[0]}");
            foreach (['_ENV' => $saved[1], '_SERVER' => $saved[2]] as $g => $v) {
                if ($v === null) {
                    unset($GLOBALS[$g][$key]);
                } else {
                    $GLOBALS[$g][$key] = $v;
                }
            }
        }
    }

    public function test_the_production_session_cookie_is_secure_http_only_and_same_site(): void
    {
        // the production .env written by the server bootstrap
        $bootstrap = (string) file_get_contents(base_path('ops/server-bootstrap.sh'));
        $this->assertMatchesRegularExpression('/^SESSION_SECURE_COOKIE=true$/m', $bootstrap);
        $this->assertDoesNotMatchRegularExpression('/^SESSION_(HTTP_ONLY|SAME_SITE)=/m', $bootstrap, 'the safe defaults are not overridden');

        $cfg = $this->sessionConfig('true');
        $this->assertTrue((bool) $cfg['secure']);
        $this->assertTrue((bool) $cfg['http_only']);
        $this->assertSame('lax', $cfg['same_site']);

        // and the cookie the application actually sets carries those flags
        config(['session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax']);
        $cookie = collect($this->get('/login')->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));
        $this->assertNotNull($cookie, 'a session cookie is set');
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_the_session_cookie_stays_secure_in_production_even_if_the_env_line_is_missing(): void
    {
        // Defence in depth: a hand-edited or partially restored .env must not silently drop the Secure flag.
        // config files are read before the environment is detected, so the default keys off APP_ENV itself
        $saved = [getenv('APP_ENV'), $_ENV['APP_ENV'] ?? null, $_SERVER['APP_ENV'] ?? null];
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
        try {
            $cfg = $this->sessionConfig(null);
        } finally {
            $saved[0] === false ? putenv('APP_ENV') : putenv('APP_ENV='.$saved[0]);
            $saved[1] === null ? $_ENV['APP_ENV'] = null : $_ENV['APP_ENV'] = $saved[1];
            $saved[2] === null ? $_SERVER['APP_ENV'] = null : $_SERVER['APP_ENV'] = $saved[2];
        }
        $this->assertTrue((bool) $cfg['secure'], 'config/session.php defaults "secure" to null (cookie not Secure) when SESSION_SECURE_COOKIE is absent');
    }

    public function test_every_state_changing_route_needs_a_csrf_token_except_the_signed_stripe_webhook(): void
    {
        $this->app['env'] = 'local'; // the CSRF middleware stands down only while the environment is "testing"
        try {
            $this->actingAs($this->alice);
            $checked = 0;
            foreach (Route::getRoutes()->getRoutes() as $route) {
                $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);
                if ($methods === []) {
                    continue;
                }
                $uri = '/'.ltrim(preg_replace('/\{[^}]+\}/', 'x', $route->uri()), '/');
                if (str_starts_with((string) $route->getName(), 'storage.local')) {
                    // the framework's signed-URL file route (see the served-disk test): refused without a signature
                    $this->assertContains($this->call('PUT', $uri, ['field' => 'value'])->getStatusCode(), [403, 404]);

                    continue;
                }
                if ($route->uri() === 'webhooks/stripe') {
                    // exempt from CSRF but not unauthenticated: an unsigned call is refused by the signature check
                    config(['services.stripe.webhook_secret' => self::WHSEC]);
                    $this->call('POST', $uri, [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')->assertStatus(400);

                    continue;
                }
                foreach ($methods as $method) {
                    $status = $this->call($method, $uri, ['field' => 'value'])->getStatusCode();
                    $this->assertSame(419, $status, "{$method} {$uri} answered {$status} without a CSRF token");
                    $checked++;
                }
            }
            $this->assertGreaterThan(40, $checked);
            // with the token the same request gets through to the controller
            $this->withSession(['_token' => 'tok'])->post('/logout', ['_token' => 'tok'])->assertRedirect('/');
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_login_is_throttled_per_account_and_address_and_per_address(): void
    {
        auth()->logout();
        $victim = User::factory()->create(['email' => 'victim@example.test', 'password' => 'Rightpass12345']);
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'victim@example.test', 'password' => 'wrong-'.$i])->assertSessionHasErrors('email');
        }
        // after ten failures from one address even the right password is refused, and nobody is signed in
        $this->post('/login', ['email' => 'victim@example.test', 'password' => 'Rightpass12345'])
            ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 15 minutes.']);
        $this->assertGuest();

        // spraying many accounts from one address hits the route limit (20 per minute)
        for ($i = 0; $i < 9; $i++) {
            $this->post('/login', ['email' => "spray{$i}@example.test", 'password' => 'x']);
        }
        $this->post('/login', ['email' => 'spray-last@example.test', 'password' => 'x'])->assertStatus(429);
        $this->assertNotNull($victim->fresh());
    }

    public function test_password_reset_requests_and_submissions_are_throttled(): void
    {
        auth()->logout();
        User::factory()->create(['email' => 'reset@example.test']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/password/email', ['email' => "r{$i}@example.test"])->assertSessionHas('status');
        }
        $this->post('/password/email', ['email' => 'reset@example.test'])->assertStatus(429);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/password/reset', ['token' => 'guess-'.$i, 'email' => 'reset@example.test', 'password' => 'Newpass12345', 'password_confirmation' => 'Newpass12345']);
        }
        $this->post('/password/reset', ['token' => 'guess-6', 'email' => 'reset@example.test', 'password' => 'Newpass12345', 'password_confirmation' => 'Newpass12345'])->assertStatus(429);
    }

    // ---------------------------------------------------------------- Stripe

    private function stripe(): FakeStripeClient
    {
        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => self::WHSEC]);
        $fake = new FakeStripeClient;
        $this->app->instance(StripeClient::class, $fake);

        return $fake;
    }

    private function webhookRaw(string $payload, ?string $signature)
    {
        $headers = ['Content-Type' => 'application/json'] + ($signature === null ? [] : ['Stripe-Signature' => $signature]);

        return $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars($headers), $payload);
    }

    private function signedEvent(string $id, string $type, array $object, ?int $ts = null, string $secret = self::WHSEC)
    {
        $payload = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'api_version' => '2024-06-20', 'created' => time(), 'livemode' => false, 'data' => ['object' => $object]]);
        $ts ??= time();

        return $this->webhookRaw($payload, "t={$ts},v1=".hash_hmac('sha256', $ts.'.'.$payload, $secret));
    }

    private function checkoutAs(Application $a)
    {
        return $this->actingAs($a->user)->post("/portal/{$a->application_number}/payments/checkout", ['tier_price_id' => $a->tier->priceFor('full')->id, 'accept_terms' => 1]);
    }

    public function test_the_webhook_refuses_missing_foreign_and_stale_signatures(): void
    {
        $this->stripe();
        $a = $this->approveServices($this->a, 'T2');
        $this->checkoutAs($a)->assertRedirect();
        $p = $a->payments()->firstOrFail();
        $paid = ['object' => 'checkout.session', 'id' => $p->stripe_checkout_session_id, 'payment_status' => 'paid', 'amount_total' => $p->amount_minor, 'currency' => 'gbp', 'payment_intent' => 'pi_x', 'metadata' => ['payment_id' => (string) $p->id]];
        $payload = json_encode(['id' => 'evt_nosig', 'object' => 'event', 'type' => 'checkout.session.completed', 'livemode' => false, 'data' => ['object' => $paid]]);

        $this->webhookRaw($payload, null)->assertStatus(400);                                         // no header at all
        $this->signedEvent('evt_other', 'checkout.session.completed', $paid, null, 'whsec_attacker')->assertStatus(400); // another secret
        $this->signedEvent('evt_old', 'checkout.session.completed', $paid, time() - 3600)->assertStatus(400);          // captured an hour ago
        $this->assertSame('INITIATED', $p->fresh()->status);
        $this->assertDatabaseCount('stripe_events', 0);
    }

    public function test_a_second_checkout_expires_the_open_one_so_only_one_session_can_be_paid(): void
    {
        $fake = $this->stripe();
        $a = $this->approveServices($this->a, 'T2');
        $this->checkoutAs($a)->assertRedirect();
        $this->checkoutAs($a)->assertRedirect();
        $payments = $a->payments()->orderBy('id')->get();
        $this->assertCount(2, $payments);
        $this->assertSame(['EXPIRED', 'INITIATED'], $payments->pluck('status')->all());
        $this->assertSame([$payments[0]->stripe_checkout_session_id], $fake->expired, 'the older session is expired at Stripe too');
        // both sessions are for the server's catalogue price, never a browser amount
        foreach ($fake->created as $params) {
            $this->assertArrayNotHasKey('price_data', $params['line_items'][0]);
        }
    }

    public function test_a_partially_refunded_fee_is_not_charged_again(): void
    {
        $fake = $this->stripe();
        $a = $this->approveServices($this->a, 'T2');
        $this->checkoutAs($a)->assertRedirect();
        $p = $a->payments()->firstOrFail();
        $this->signedEvent('evt_paid', 'checkout.session.completed', ['object' => 'checkout.session', 'id' => $p->stripe_checkout_session_id, 'payment_status' => 'paid', 'amount_total' => $p->amount_minor, 'currency' => 'gbp', 'payment_intent' => 'pi_paid', 'metadata' => ['payment_id' => (string) $p->id]])->assertOk();
        $this->signedEvent('evt_part', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_paid', 'amount_refunded' => 5000])->assertOk();
        $this->assertSame('REFUNDED_PARTIAL', $p->fresh()->status);

        // the fee has been paid (less a goodwill refund): a new checkout for the same fee must not be opened
        $this->checkoutAs($a);
        $this->assertCount(1, $fake->created, 'a second Stripe Checkout session was created for a fee that is already paid (REFUNDED_PARTIAL)');
    }

    // ---------------------------------------------------------------- Submission

    private function completeForm(Application $a): void
    {
        $form = [
            'personal' => ['legal_first_names' => 'Ada', 'legal_surname' => 'Okonkwo', 'date_of_birth' => '2007-01-01', 'nationality' => 'Nigerian', 'phone' => '+234', 'country_of_residence' => 'Nigeria'],
            'study' => ['course_family' => 'medicine', 'entry_type' => 'standard', 'intake_year' => 2028, 'ucas_status' => 'not_started'],
            'secondary' => ['sittings' => [['board' => 'WAEC', 'year' => 2025, 'subjects' => [['subject' => 'English', 'grade' => 'B2'], ['subject' => 'Maths', 'grade' => 'A1'], ['subject' => 'Biology', 'grade' => 'A1']]]]],
            'post_secondary' => ['none' => 1],
            'english' => ['route' => 'NONE_YET'],
            'tests' => ['ucat_status' => 'planned', 'gamsat_status' => 'not_planned'],
            'experience' => ['statement_status' => 'draft'],
            'referees' => ['referees' => [['name' => 'A', 'role' => 'Teacher', 'institution' => 'School', 'email' => 'a@b.test']], 'consent_contact_referees' => 1],
            'declarations' => ['accurate' => 1, 'data_processing' => 1, 'terms' => 1, 'no_guarantee' => 1],
        ];
        $status = collect(array_keys(FormSteps::all()))->mapWithKeys(fn ($s) => [$s => 'complete'])->all();
        $a->forceFill(['form' => $form, 'section_status' => $status])->save();
    }

    public function test_nothing_reaches_a_university_without_a_live_recorded_student_approval(): void
    {
        $admin = $this->staff('admin');
        $a = $this->approveServices($this->a, 'T2');
        $n = $a->application_number;
        $this->completeForm($a);
        $a->documents()->get()->each(fn ($d) => $d->transition(DocumentStatus::ACCEPTED, $admin->id));
        $u = University::create(['slug' => 'leics', 'name' => 'University of Leicester', 'international_policy' => 'accepts']);
        $this->asStaff($admin)->post("/admin/applications/{$n}/submissions", ['university_id' => $u->id, 'intake' => 'September 2028', 'route_code' => 'PATHWAY_PROVIDER'])->assertSessionHas('status');
        $sub = Submission::where('application_id', $a->id)->firstOrFail();

        // the stage cannot be forced past approval
        foreach (['SUBMITTED', 'STUDENT_APPROVED', 'OFFER_CONDITIONAL'] as $stage) {
            $this->asStaff($admin)->post("/admin/applications/{$n}/stage", ['stage_override' => $stage])->assertSessionHasErrors('stage_override');
        }
        // the student cannot "record" a submission that was never approved
        $this->actingAs($this->alice)->post("/portal/{$n}/submissions/{$sub->id}/recorded", ['external_reference' => 'X', 'submitted_on' => now()->toDateString()])->assertForbidden();
        // a submission addressed through another application's URL is not found
        $this->asStaff($admin)->post("/admin/applications/{$this->b->application_number}/submissions/{$sub->id}", ['status' => 'SUBMITTED', 'external_reference' => 'X'])->assertNotFound();
        $this->assertSame('PROPOSED', $sub->fresh()->status);

        // the student approves
        $a->payments()->create(['tier_price_id' => $a->tier->priceFor('full')->id, 'status' => 'SUCCEEDED', 'amount_minor' => 69500, 'currency' => 'GBP', 'method' => 'STRIPE', 'succeeded_at' => now()]);
        $this->asStaff($admin)->post("/admin/applications/{$n}/stage", ['stage_override' => 'READY_FOR_STUDENT_APPROVAL'])->assertSessionHas('status');
        app(StageResolver::class)->sync($a->fresh());
        $page = $this->actingAs($this->alice)->get("/portal/{$n}/approve")->assertOk();
        preg_match('/name="hash" value="([a-f0-9]{64})"/', $page->getContent(), $m);
        // another student cannot approve on Alice's behalf
        $this->actingAs($this->bob)->post("/portal/{$n}/approve", ['typed_name' => 'Bob', 'confirm' => 1, 'hash' => $m[1]])->assertForbidden();
        $this->assertSame(0, Authorisation::count());
        $this->actingAs($this->alice)->post("/portal/{$n}/approve", ['typed_name' => 'Ada Okonkwo', 'confirm' => 1, 'hash' => $m[1]])->assertRedirect();
        $this->assertSame('AUTHORISED', $sub->fresh()->status);
        $this->assertSame(Stage::STUDENT_APPROVED, $a->fresh()->stage);

        // once the approval is revoked, staff cannot mark the package ready or submitted
        Authorisation::firstOrFail()->update(['revoked_at' => now(), 'revoked_reason' => 'audit test']);
        foreach (['PACKAGE_READY', 'SUBMITTED'] as $status) {
            $this->asStaff($admin)->post("/admin/applications/{$n}/submissions/{$sub->id}", ['status' => $status, 'external_reference' => 'REF1'])->assertSessionHas('error');
        }
        $this->assertNotSame('SUBMITTED', $sub->fresh()->status);
        $this->assertDatabaseMissing('application_events', ['application_id' => $a->id, 'type' => 'submission.sent']);
    }
}
