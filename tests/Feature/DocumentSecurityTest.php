<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Negative paths of the document pipeline (P0: students upload passports and certificates). Every attempt that is not
 * the owning student or signed-in staff with two-step verification must fail, and nothing reaches a public path.
 */
class DocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    private Application $a;

    private Application $b;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
        $this->seed(PlatformSeeder::class);
        [$this->alice, $this->bob] = [User::factory()->create(), User::factory()->create()];
        $this->actingAs($this->alice)->post('/portal/start', ['intake_year' => 2028]);
        $this->actingAs($this->bob)->post('/portal/start', ['intake_year' => 2028]);
        $this->a = Application::where('user_id', $this->alice->id)->firstOrFail();
        $this->b = Application::where('user_id', $this->bob->id)->firstOrFail();
    }

    private function upload(User $user, Application $app, string $name = 'dummy.pdf', ?string $bytes = null)
    {
        $doc = $app->documents()->where('code', 'PASSPORT')->firstOrFail();
        $bytes ??= file_get_contents(base_path('ops/qa/fixtures/dummy-test-document.pdf'));

        return [$doc, $this->actingAs($user)->post("/portal/{$app->application_number}/documents/{$doc->id}", ['file' => UploadedFile::fake()->createWithContent($name, $bytes)])];
    }

    public function test_only_the_owner_and_signed_in_staff_can_read_a_document(): void
    {
        [$doc] = $this->upload($this->alice, $this->a);
        $v = $doc->fresh()->versions()->firstOrFail();
        $own = "/portal/{$this->a->application_number}/documents/{$doc->id}/v/{$v->id}";
        $bobDoc = $this->b->documents()->where('code', 'PASSPORT')->firstOrFail();

        // signed out: sent to sign-in, never the file
        auth()->logout();
        $this->get($own)->assertRedirect('/login');
        // another student: through Alice's URL, or by pairing Alice's version with Bob's own application or document
        $this->actingAs($this->bob)->get($own)->assertForbidden();
        $this->actingAs($this->bob)->get("/portal/{$this->b->application_number}/documents/{$doc->id}/v/{$v->id}")->assertForbidden();
        $this->actingAs($this->bob)->get("/portal/{$this->b->application_number}/documents/{$bobDoc->id}/v/{$v->id}")->assertForbidden();
        // guessing neighbouring ids finds nothing either
        $this->actingAs($this->bob)->get("/portal/{$this->a->application_number}/documents/{$doc->id}/v/".($v->id + 1))->assertNotFound();
        // a student cannot open the staff preview
        $this->actingAs($this->alice)->get(route('admin.applications.document', [$this->a, $doc]))->assertForbidden();

        // staff need two-step verification before any document opens
        $staff = User::factory()->create();
        $staff->forceFill(['role' => 'staff', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($staff)->get(route('admin.applications.document', [$this->a, $doc]))->assertRedirect();
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->get(route('admin.applications.document', [$this->a, $doc]))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertDatabaseHas('document_access_log', ['document_version_id' => $v->id, 'user_id' => $staff->id, 'purpose' => 'preview']);
        // a document id from another application under this application's URL is refused
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->get(route('admin.applications.document', [$this->a, $bobDoc]))->assertNotFound();
    }

    public function test_uploads_are_validated_by_content_and_size_and_stored_privately(): void
    {
        // an executable renamed to .pdf, a text file, and an oversized file are all refused; nothing is stored
        [$doc, $r] = $this->upload($this->alice, $this->a, 'passport.pdf', "MZ\x90\x00".str_repeat("\x00", 200));
        $r->assertSessionHasErrors('file');
        [, $r] = $this->upload($this->alice, $this->a, 'passport.pdf', 'just some text pretending to be a pdf');
        $r->assertSessionHasErrors('file');
        $big = UploadedFile::fake()->create('passport.pdf', 10241, 'application/pdf');
        $this->actingAs($this->alice)->post("/portal/{$this->a->application_number}/documents/{$doc->id}", ['file' => $big])->assertSessionHasErrors('file');
        $this->assertSame(0, $doc->fresh()->versions()->count());
        // nobody but the owner can upload into an application
        [, $r] = $this->upload($this->bob, $this->a);
        $r->assertForbidden();

        // an accepted upload is encrypted on the private disk, outside the web root
        [$doc, $r] = $this->upload($this->alice, $this->a);
        $r->assertSessionHasNoErrors();
        $v = $doc->fresh()->versions()->firstOrFail();
        $this->assertTrue((bool) $v->encrypted);
        $this->assertStringNotContainsString('%PDF', Storage::disk('private')->get($v->path), 'bytes at rest are encrypted');
        $root = realpath(config('filesystems.disks.private.root')) ?: config('filesystems.disks.private.root');
        $this->assertStringStartsNotWith(public_path(), (string) $root, 'documents never live under the web root');
        $this->assertFalse(file_exists(public_path('storage')), 'no public storage link');
    }
}
