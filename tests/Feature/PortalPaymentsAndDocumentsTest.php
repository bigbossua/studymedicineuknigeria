<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Notifications\StaffNotification;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalPaymentsAndDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $admin;

    private Application $application;

    private TierPrice $price;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
        $this->seed(PlatformSeeder::class);
        $this->student = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $tier = ServiceTier::where('code', 'T2')->first();
        $this->actingAs($this->student)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028])->assertRedirect();
        $this->application = Application::first();
        $this->price = TierPrice::where('service_tier_id', $tier->id)->first();
        $this->price->update(['amount_minor' => 25000, 'currency' => 'GBP']);
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession([EnsureTwoFactor::SESSION_KEY => $this->admin->id]);
    }

    public function test_card_checkout_is_refused_until_stripe_is_configured_and_bank_transfer_goes_to_manual_review(): void
    {
        config(['services.stripe.secret' => null]);
        $n = $this->application->application_number;
        $this->actingAs($this->student)->get("/portal/$n/payments")->assertOk()->assertSee('Bank tran');
        $this->actingAs($this->student)->post("/portal/$n/payments/checkout", ['tier_price_id' => $this->price->id, 'accept_terms' => 1])->assertSessionHas('error');
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($this->student)->post("/portal/$n/payments/manual", ['tier_price_id' => $this->price->id, 'accept_terms' => 1, 'reference' => 'GTB 12345'])->assertSessionHas('status');
        $payment = $this->application->payments()->first();
        $this->assertSame(['MANUAL_REVIEW', 'MANUAL_TRANSFER', 25000], [$payment->status, $payment->method, $payment->amount_minor]);
        Notification::assertSentTo($this->admin, StaffNotification::class);
        $this->assertDatabaseHas('funnel_events', ['name' => 'payment_started', 'tier' => 'T2']);

        // a price from another tier, or without an amount, is refused
        $other = TierPrice::where('service_tier_id', '!=', $this->price->service_tier_id)->first();
        $this->actingAs($this->student)->post("/portal/$n/payments/manual", ['tier_price_id' => $other->id, 'accept_terms' => 1])->assertForbidden();

        // staff confirm the transfer: payment succeeds, student notified, funnel mirrors it
        $this->asAdmin()->post("/admin/applications/$n/payments/{$payment->id}/confirm", ['decision' => 'confirm', 'note' => 'Seen on statement'])->assertSessionHas('status');
        $this->assertSame('SUCCEEDED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->succeeded_at);
        Notification::assertSentTo($this->student, ApplicationNotification::class, fn ($x) => $x->type === 'payment.succeeded');
        $this->assertDatabaseHas('funnel_events', ['name' => 'payment_completed']);
        $this->assertDatabaseHas('admin_actions', ['action' => 'payment.manual', 'target_id' => $payment->id]);

        // the student cannot confirm their own transfer
        $this->actingAs($this->student)->post("/admin/applications/$n/payments/{$payment->id}/confirm", ['decision' => 'confirm'])->assertForbidden();
    }

    public function test_passport_images_are_re_encoded_encrypted_at_rest_and_streamed_back_with_an_access_log(): void
    {
        $n = $this->application->application_number;
        $doc = $this->application->documents()->where('code', 'PASSPORT')->first();
        $this->assertNotNull($doc, 'the checklist must include the passport');

        $file = UploadedFile::fake()->image('passport-photo.png', 3600, 2400);
        $this->actingAs($this->student)->post("/portal/$n/documents/{$doc->id}", ['file' => $file])->assertRedirect();
        $version = $doc->fresh()->versions()->first();
        $this->assertTrue((bool) $version->encrypted);
        $this->assertStringEndsWith('.png.enc', $version->path);
        $this->assertSame('image/png', $version->mime);

        $stored = Storage::disk('private')->get($version->path);
        $this->assertStringNotContainsString("\x89PNG", $stored, 'bytes on disk must not be a readable image');

        $response = $this->actingAs($this->student)->get("/portal/$n/documents/{$doc->id}/v/{$version->id}");
        $response->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Cache-Control', 'no-store, private');
        $bytes = $response->streamedContent();
        $this->assertStringStartsWith("\x89PNG", $bytes);
        [$w, $h] = getimagesizefromstring($bytes);
        $this->assertLessThanOrEqual(3000, max($w, $h), 'oversized images are scaled down on upload');
        $this->assertDatabaseHas('document_access_log', ['document_version_id' => $version->id, 'user_id' => $this->student->id, 'purpose' => 'download']);

        $this->actingAs(User::factory()->create())->get("/portal/$n/documents/{$doc->id}/v/{$version->id}")->assertForbidden();
        $this->asAdmin()->get("/portal/$n/documents/{$doc->id}/v/{$version->id}")->assertOk();
    }

    public function test_students_can_export_their_data_as_json(): void
    {
        $response = $this->actingAs($this->student)->get('/portal/profile/export');
        $response->assertOk()->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $data = json_decode($response->streamedContent(), true);
        $this->assertSame($this->student->email, $data['user']['email']);
        $this->assertSame($this->application->application_number, $data['applications'][0]['application_number']);
    }

    public function test_export_never_contains_staff_only_fields_and_downloads_use_safe_names(): void
    {
        $this->application->forceFill(['staff_notes' => 'INTERNAL: chase references', 'assigned_staff_id' => $this->admin->id])->save();
        $doc = $this->application->documents()->where('code', 'PASSPORT')->first();
        $this->actingAs($this->student)->post("/portal/{$this->application->application_number}/documents/{$doc->id}", ['file' => UploadedFile::fake()->image('my passport "scan".png', 800, 600)])->assertRedirect();
        $version = $doc->fresh()->versions()->first();

        $json = $this->actingAs($this->student)->get('/portal/profile/export')->streamedContent();
        foreach (['INTERNAL: chase references', 'staff_notes', 'assigned_staff_id', 'stage_override', '"path"', 'key_id', 'scan_status', 'snapshot'] as $needle) {
            $this->assertStringNotContainsString($needle, $json, "export leaks {$needle}");
        }
        $this->assertStringContainsString($this->application->application_number, $json);

        $r = $this->actingAs($this->student)->get("/portal/{$this->application->application_number}/documents/{$doc->id}/v/{$version->id}");
        $this->assertStringContainsString('passport-v1.png', $r->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('scan', $r->headers->get('Content-Disposition'));
        $r = $this->asAdmin()->get("/admin/applications/{$this->application->application_number}/documents/{$doc->id}/view");
        $this->assertStringContainsString('passport-v1.png', $r->headers->get('Content-Disposition'));
    }

    public function test_macro_enabled_word_files_are_refused_even_when_renamed_to_docx(): void
    {
        $doc = $this->application->documents()->whereIn('code', ['STATEMENT', 'CV', 'OTHER'])->first() ?? $this->application->documents()->first();
        $doc->forceFill(['code' => 'STATEMENT'])->save();
        $path = storage_path('framework/testing/macro.docx');
        @mkdir(dirname($path), 0777, true);
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.ms-word.document.macroEnabled.main+xml"/></Types>');
        $zip->addFromString('word/document.xml', '<w:document/>');
        $zip->addFromString('word/vbaProject.bin', str_repeat('A', 64));
        $zip->addFromString('_rels/.rels', '<Relationships/>');
        $zip->close();
        $file = new UploadedFile($path, 'statement.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $this->actingAs($this->student)->post("/portal/{$this->application->application_number}/documents/{$doc->id}", ['file' => $file])->assertSessionHasErrors('file');
        $this->assertSame(0, $doc->fresh()->versions()->count());
        @unlink($path);
    }

    public function test_autosave_keeps_only_declared_fields_and_inactive_prices_cannot_be_bought(): void
    {
        $n = $this->application->application_number;
        $this->actingAs($this->student)->postJson("/portal/$n/application/personal", ['legal_first_names' => 'Ada', 'evil' => str_repeat('x', 5000), 'role' => 'admin'])->assertOk();
        $form = $this->application->fresh()->form['personal'];
        $this->assertSame('Ada', $form['legal_first_names']);
        $this->assertArrayNotHasKey('evil', $form);
        $this->assertArrayNotHasKey('role', $form);

        $this->price->update(['active' => false]);
        $this->actingAs($this->student)->post("/portal/$n/payments/manual", ['tier_price_id' => $this->price->id, 'accept_terms' => 1])->assertNotFound();
    }

    public function test_pdf_active_content_is_caught_inside_compressed_streams_and_name_escapes(): void
    {
        $doc = $this->application->documents()->where('code', 'PASSPORT')->first();
        $n = $this->application->application_number;
        $pdf = fn (string $body) => "%PDF-1.5\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n".$body."\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $compressed = gzcompress('<< /Type /Action /S /JavaScript /JS (app.alert(1)) >>');
        $hidden = $pdf("3 0 obj\n<< /Type /ObjStm /Filter /FlateDecode /Length ".strlen($compressed)." >>\nstream\n".$compressed."\nendstream\nendobj");
        $escaped = $pdf("3 0 obj\n<< /S /J#61vaScript /JS (x) >>\nendobj");
        $plain = $pdf("3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj");

        foreach (['hidden' => $hidden, 'escaped' => $escaped] as $label => $bytes) {
            $file = UploadedFile::fake()->createWithContent("$label.pdf", $bytes);
            $this->actingAs($this->student)->post("/portal/$n/documents/{$doc->id}", ['file' => $file])->assertSessionHasErrors('file');
        }
        $this->assertSame(0, $doc->fresh()->versions()->count());
        $this->actingAs($this->student)->post("/portal/$n/documents/{$doc->id}", ['file' => UploadedFile::fake()->createWithContent('plain.pdf', $plain)])->assertRedirect();
        $this->assertSame(1, $doc->fresh()->versions()->count());
    }
}
