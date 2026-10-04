<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\FactChange;
use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\User;
use App\Support\FactSeeding;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Every change to what the public may see leaves a trail: who or what changed it, and the value before and after. */
class FactAuditTest extends TestCase
{
    use RefreshDatabase;

    private function fact(array $attrs = []): ReferenceFact
    {
        $t = Topic::firstOrCreate(['slug' => 'ucas-2027'], ['title' => 'UCAS', 'cycle' => '2027']);

        return $t->facts()->create($attrs + ['key' => 'deadline_medicine', 'value_text' => '15 October 2026, 18:00 (UK time)', 'academic_year' => '2027',
            'source_url' => 'https://www.ucas.com/dates', 'source_type' => 'official', 'verification_status' => ReferenceFact::VERIFY_ON_PAGE]);
    }

    public function test_an_admin_verification_records_the_reviewer_and_the_status_change(): void
    {
        $fact = $this->fact();
        $admin = User::factory()->create(['name' => 'Reviewer One']);
        $admin->forceFill(['role' => 'staff', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])
            ->post(route('admin.reference.update', $fact), ['decision' => 'verify', 'value_text' => '15 October 2026, 18:00 (UK time)', 'source_url' => 'https://www.ucas.com/dates'])->assertRedirect();

        $change = FactChange::where('reference_fact_id', $fact->id)->sole();
        $this->assertSame('admin', $change->via);
        $this->assertSame($admin->id, $change->user_id);
        $this->assertSame(ReferenceFact::VERIFY_ON_PAGE, $change->before['verification_status']);
        $this->assertSame(ReferenceFact::VERIFIED, $change->after['verification_status']);
        $this->assertArrayHasKey('verified_at', $change->after);

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])
            ->get(route('admin.reference.index', ['status' => ReferenceFact::VERIFIED]))->assertOk()->assertSee('History (1)')->assertSee('Reviewer One');
    }

    public function test_the_source_watcher_and_the_worksheet_import_are_named_in_the_trail(): void
    {
        $watched = $this->fact(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'reviewed_at' => now(), 'source_url' => 'https://www.ucas.com/gone']);
        Http::fake(['https://www.ucas.com/gone' => Http::response('', 404)]);
        $this->artisan('smukn:sources-check')->assertSuccessful();
        $change = FactChange::where('reference_fact_id', $watched->id)->sole();
        $this->assertSame('source watcher', $change->via);
        $this->assertNull($change->user_id);
        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $change->after['verification_status']);
        $this->assertSame(ReferenceFact::VERIFIED, $change->before['verification_status'], 'the old verified state is preserved in the trail');

        $pending = $this->fact(['key' => 'deadline_main', 'value_text' => '13 January 2027']);
        $dec = 'storage/framework/testing/decisions-audit.csv';
        File::ensureDirectoryExists(dirname(base_path($dec)));
        File::put(base_path($dec), "ref,decision,verified_value,new_source_url,reviewer_note,verified_on\ntopic:ucas-2027:deadline_main:2027,verified,14 January 2027,,Date moved on the page,2026-10-04\n");
        $this->artisan('smukn:facts-import', ['file' => $dec])->assertSuccessful();
        $change = FactChange::where('reference_fact_id', $pending->id)->sole();
        $this->assertStringStartsWith('worksheet import decisions-audit.csv ', $change->via);
        $this->assertSame('13 January 2027', $change->before['value_text']);
        $this->assertSame('14 January 2027', $change->after['value_text']);
        File::delete(base_path($dec));
    }

    public function test_reference_sync_refreshes_are_attributed_and_reviewed_facts_are_left_alone(): void
    {
        $topic = Topic::firstOrCreate(['slug' => 'ucas-2027'], ['title' => 'UCAS', 'cycle' => '2027']);
        $open = $this->fact();
        $reviewed = $this->fact(['key' => 'deadline_main', 'value_text' => '13 January 2027', 'reviewed_at' => now()]);

        FactSeeding::upsert($topic, ['key' => 'deadline_medicine', 'academic_year' => '2027', 'qualification_code' => null], ['value_text' => '15 October 2026'], ReferenceFact::VERIFY_ON_PAGE);
        FactSeeding::upsert($topic, ['key' => 'deadline_main', 'academic_year' => '2027', 'qualification_code' => null], ['value_text' => 'changed by a seeder'], ReferenceFact::VERIFY_ON_PAGE);

        $this->assertSame('reference sync', FactChange::where('reference_fact_id', $open->id)->sole()->via);
        $this->assertSame(0, FactChange::where('reference_fact_id', $reviewed->id)->count());
        $this->assertSame('13 January 2027', $reviewed->fresh()->value_text);
    }

    public function test_staging_shows_only_verified_facts_like_production(): void
    {
        $pending = $this->fact();
        $verified = $this->fact(['key' => 'deadline_main', 'value_text' => '13 January 2027', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);

        $this->assertTrue($pending->isPublishable(), 'tests and local development show everything');
        $this->app['env'] = 'staging';
        try {
            $this->assertFalse($pending->isPublishable(), 'staging hides unverified facts, so the owner reviews what would go live');
            $this->assertTrue($verified->isPublishable());
            config(['site.publish_unverified' => true]);
            $this->assertTrue($pending->isPublishable(), 'only an explicit setting shows unverified facts outside development');
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
