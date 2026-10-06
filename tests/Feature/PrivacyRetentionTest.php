<?php

namespace Tests\Feature;

use App\Enums\Stage;
use App\Models\Application;
use App\Models\FunnelEvent;
use App\Models\User;
use App\Services\Reference\DatasetImporter;
use App\Support\Funnel;
use Database\Seeders\PlatformSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The privacy notice's retention, erasure and analytics promises, checked against what the code does (2026-10-06 audit). */
class PrivacyRetentionTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private Application $a;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
        $this->seed(PlatformSeeder::class);
        $this->alice = User::factory()->create(['email' => 'alice@example.test', 'phone' => '+2348000000000']);
        $this->actingAs($this->alice)->post('/portal/start', ['intake_year' => 2028]);
        $this->a = Application::where('user_id', $this->alice->id)->firstOrFail();
    }

    public function test_the_retention_job_is_scheduled_weekly(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains((string) $e->command, 'smukn:retention'));
        $this->assertCount(1, $events, 'smukn:retention is on the schedule');
        $this->assertSame('10 4 * * 0', $events->first()->expression);
    }

    public function test_anonymisation_at_24_months_clears_every_free_text_field_and_keeps_the_fingerprint(): void
    {
        $doc = $this->a->documents()->where('code', 'PASSPORT')->firstOrFail();
        $this->actingAs($this->alice)->post("/portal/{$this->a->application_number}/documents/{$doc->id}", ['file' => UploadedFile::fake()->createWithContent('alice-passport.pdf', file_get_contents(base_path('ops/qa/fixtures/dummy-test-document.pdf')))]);
        $doc->forceFill(['staff_note' => 'Alice, please rescan page 2'])->save();
        $this->a->record('student.declined', ['note' => 'I am Alice and I disagree', 'step' => 'review']);
        $sub = DB::table('submissions')->insertGetId(['application_id' => $this->a->id, 'university_id' => null, 'route_code' => 'UCAS', 'status' => 'PROPOSED', 'notes' => 'Alice prefers Leeds', 'external_reference' => 'UCAS-123-456', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('authorisations')->insert(['application_id' => $this->a->id, 'submission_id' => $sub, 'approved_by_user_id' => $this->alice->id, 'approved_at' => now(), 'declaration_version' => '1', 'typed_name' => 'Alice Example', 'snapshot_hash' => str_repeat('a', 64), 'snapshot' => json_encode(['form' => ['name' => 'Alice Example']])]);
        $this->a->forceFill(['stage' => Stage::CLOSED, 'closed_at' => now()->subMonths(25), 'closed_reason' => 'Alice moved to another agent'])->save();

        $this->artisan('smukn:retention')->assertSuccessful();

        $a = $this->a->fresh();
        $this->assertNotNull($a->anonymised_at);
        $this->assertNull($a->form);
        $this->assertStringNotContainsString('Alice', (string) $a->closed_reason);
        $this->assertStringNotContainsString('Alice', (string) DB::table('application_events')->where('application_id', $a->id)->pluck('payload')->implode(' '));
        $this->assertStringContainsString('review', (string) DB::table('application_events')->where('application_id', $a->id)->where('type', 'student.declined')->value('payload'), 'non-personal keys stay');
        $this->assertNull(DB::table('documents')->where('id', $doc->id)->value('staff_note'));
        $this->assertSame('removed', DB::table('document_versions')->where('document_id', $doc->id)->value('original_filename'));
        $this->assertNull(DB::table('submissions')->where('id', $sub)->value('notes'));
        $this->assertNull(DB::table('submissions')->where('id', $sub)->value('external_reference'));
        $auth = DB::table('authorisations')->where('application_id', $a->id)->first();
        $this->assertStringNotContainsString('Alice', $auth->snapshot.$auth->typed_name);
        $this->assertSame(str_repeat('a', 64), $auth->snapshot_hash, 'the approval fingerprint is kept as evidence');
        $this->assertNotNull(Cache::get('retention.last_run'), 'each run is on record for Admin → Launch');
    }

    public function test_payment_and_approval_records_go_after_six_years_and_old_leads_and_analytics_after_24_months(): void
    {
        DB::table('payments')->insert(['application_id' => $this->a->id, 'amount_minor' => 12500, 'currency' => 'GBP', 'status' => 'SUCCEEDED', 'method' => 'stripe', 'created_at' => now()->subYears(7), 'updated_at' => now()->subYears(7)]);
        DB::table('leads')->insert(['email' => 'old@example.test', 'name' => 'Old Lead', 'created_at' => now()->subMonths(30), 'updated_at' => now()->subMonths(30)]);
        DB::table('leads')->insert(['email' => 'new@example.test', 'name' => 'New Lead', 'created_at' => now(), 'updated_at' => now()]);
        FunnelEvent::create(['name' => 'course_viewed', 'occurred_at' => now()->subMonths(30)]);
        $this->a->forceFill(['stage' => Stage::CLOSED, 'closed_at' => now()->subYears(7), 'anonymised_at' => now()->subYears(5)])->save();

        $this->artisan('smukn:retention')->assertSuccessful();

        $this->assertSame(0, DB::table('payments')->where('application_id', $this->a->id)->count());
        $this->assertSame(['new@example.test'], DB::table('leads')->pluck('email')->all());
        $this->assertSame(0, FunnelEvent::where('occurred_at', '<', now()->subMonths(24))->count());
    }

    public function test_an_application_left_untouched_for_24_months_is_closed_so_the_clock_can_start(): void
    {
        $this->a->forceFill(['last_activity_at' => now()->subMonths(25)])->saveQuietly();
        DB::table('applications')->where('id', $this->a->id)->update(['updated_at' => now()->subMonths(25)]);

        $this->artisan('smukn:retention')->assertSuccessful();

        $a = $this->a->fresh();
        $this->assertSame(Stage::CLOSED, $a->stage);
        $this->assertNotNull($a->closed_at);
    }

    public function test_a_deletion_request_is_recorded_and_erasure_removes_files_and_identity(): void
    {
        $doc = $this->a->documents()->where('code', 'PASSPORT')->firstOrFail();
        $this->actingAs($this->alice)->post("/portal/{$this->a->application_number}/documents/{$doc->id}", ['file' => UploadedFile::fake()->createWithContent('p.pdf', file_get_contents(base_path('ops/qa/fixtures/dummy-test-document.pdf')))]);
        $path = $doc->fresh()->versions()->firstOrFail()->path;
        DB::table('leads')->insert(['email' => 'alice@example.test', 'name' => 'Alice', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->alice)->post('/portal/profile/delete', ['confirm' => '1'])->assertRedirect();
        $this->assertNotNull($this->alice->fresh()->deletion_requested_at);

        $this->artisan('smukn:erase-account', ['email' => 'alice@example.test'])->assertSuccessful();

        $u = $this->alice->fresh();
        $this->assertNotNull($u->erased_at);
        $this->assertStringStartsWith('removed-', $u->email);
        $this->assertNull($u->phone);
        $this->assertFalse(Storage::disk('private')->exists($path), 'every document file is deleted');
        $this->assertSame(0, DB::table('leads')->where('email', 'alice@example.test')->count());
        $this->assertNotNull($this->a->fresh()->anonymised_at);
        $this->assertSame(Stage::WITHDRAWN, $this->a->fresh()->stage);
    }

    public function test_erasure_waits_while_a_submission_is_with_a_university(): void
    {
        $this->a->forceFill(['stage' => Stage::SUBMITTED])->save();
        $this->artisan('smukn:erase-account', ['email' => 'alice@example.test'])->assertFailed();
        $this->assertNull($this->alice->fresh()->erased_at);
    }

    public function test_the_data_export_includes_the_eligibility_check_record(): void
    {
        DB::table('leads')->insert(['email' => 'alice@example.test', 'name' => 'Alice', 'eligibility_answers' => json_encode(['qualification' => 'WAEC']), 'created_at' => now(), 'updated_at' => now()]);
        $json = $this->actingAs($this->alice)->get('/portal/profile/export')->streamedContent();
        $this->assertStringContainsString('"eligibility_checks"', $json);
        $this->assertStringContainsString('WAEC', $json);
    }

    public function test_analytics_never_store_the_application_number_or_a_reversible_hash(): void
    {
        $this->actingAs($this->alice)->get("/portal/{$this->a->application_number}/application/personal");
        Funnel::track('step_completed', ['step' => 'personal', 'actor' => 'student'], $this->a);

        $rows = FunnelEvent::whereNotNull('application_hash')->get();
        $this->assertNotEmpty($rows);
        foreach ($rows as $r) {
            $this->assertNotSame(hash('sha256', $this->a->application_number), $r->application_hash, 'a plain SHA-256 of a sequential number can be reversed');
            $this->assertStringNotContainsString($this->a->application_number, (string) $r->source_page);
            $this->assertStringNotContainsString($this->a->application_number, json_encode($r->properties));
        }
        $this->assertSame('portal/{application}/documents', Funnel::maskedPath("portal/{$this->a->application_number}/documents"));
    }

    public function test_the_stored_stripe_event_keeps_no_customer_details(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $payload = json_encode(['id' => 'evt_1', 'type' => 'checkout.session.completed', 'created' => time(), 'data' => ['object' => ['id' => 'cs_1', 'object' => 'checkout.session', 'amount_total' => 12500, 'currency' => 'gbp', 'payment_status' => 'paid', 'customer_details' => ['name' => 'Alice Example', 'email' => 'alice@example.test', 'address' => ['line1' => '1 Road']], 'metadata' => []]]]);
        $t = time();
        $sig = 't='.$t.',v1='.hash_hmac('sha256', "{$t}.{$payload}", 'whsec_test');
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload);

        $stored = (string) DB::table('stripe_events')->where('stripe_event_id', 'evt_1')->value('payload');
        $this->assertNotSame('', $stored);
        $this->assertStringNotContainsString('alice', strtolower($stored));
        $this->assertStringContainsString('12500', $stored);
    }

    public function test_the_directory_columns_follow_verified_wording_not_the_raw_dataset(): void
    {
        $importer = new DatasetImporter;
        $route = new \ReflectionMethod($importer, 'routeFrom');
        $test = new \ReflectionMethod($importer, 'testFrom');
        $this->assertSame('UCAS', $route->invoke($importer, 'UCAS only; late or direct applications are not accepted'));
        $this->assertSame('BOTH', $route->invoke($importer, 'UCAS, or directly to the university for international applicants'));
        $this->assertSame('NONE', $test->invoke($importer, 'None (no UCAT required)'));
        $this->assertSame('NONE', $test->invoke($importer, 'UCAT NOT required for international applicants'));
        $this->assertSame('NONE', $test->invoke($importer, 'Home applicants: UCAT or GAMSAT; international applicants: no admissions test score required'));
        $this->assertSame('UCAT', $test->invoke($importer, 'UCAT; BMAT and GAMSAT not accepted instead'));
        $this->assertSame('UCAT/GAMSAT', $test->invoke($importer, 'UCAT, GAMSAT or MCAT'));
    }
}
