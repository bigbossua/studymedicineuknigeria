<?php

namespace Tests\Feature;

use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\StaffNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** An old verified value never outlives a material change to its official page. */
class SourceCheckTest extends TestCase
{
    use RefreshDatabase;

    private function verified(string $key, ?string $text, ?float $number, string $url): ReferenceFact
    {
        $t = Topic::firstOrCreate(['slug' => 'ucas-2027'], ['title' => 'UCAS', 'cycle' => '2027']);

        return $t->facts()->create(['key' => $key, 'value_text' => $text, 'value_number' => $number, 'source_url' => $url, 'source_type' => 'official', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'reviewed_at' => now()]);
    }

    public function test_a_changed_page_unverifies_only_the_facts_whose_value_is_gone(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $url = 'https://www.ucas.com/dates';
        $deadline = $this->verified('deadline_medicine', '15 October 2026, 18:00 (UK time)', null, $url);
        $fee = $this->verified('application_fee_gbp', null, 28.95, $url);
        $pages = ['<html><body><p>Medicine deadline: 15 October 2026, 18:00 (UK time)</p><p>Fee £28.95</p><script>var t=1</script></body></html>',
            '<html><body><p>Medicine deadline: 15 October 2026, 18:00 (UK time)</p><p>Fee £28.95</p><script>var t=2</script></body></html>',
            '<html><body><p>Medicine deadline: 16 October 2026, 18:00 (UK time)</p><p>Application fee &pound;28.95 &nbsp;now</p></body></html>'];
        Http::fakeSequence()->push($pages[0])->push($pages[1])->push($pages[2]);

        $this->artisan('smukn:sources-check')->expectsOutputToContain('1 first fingerprint')->assertSuccessful();
        $this->artisan('smukn:sources-check')->expectsOutputToContain('0 fact(s) un-verified')->assertSuccessful(); // script change only
        $this->artisan('smukn:sources-check')->expectsOutputToContain('1 fact(s) un-verified')->assertSuccessful();

        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $deadline->fresh()->verification_status, 'the deadline wording is no longer on the page');
        $this->assertStringContainsString('verified value was no longer found', $deadline->fresh()->notes);
        $this->assertSame(ReferenceFact::VERIFIED, $fee->fresh()->verification_status, 'the fee is still published as 28.95');
        Notification::assertSentTo($admin, StaffNotification::class);
    }

    public function test_a_removed_page_unverifies_its_facts_and_a_server_error_changes_nothing(): void
    {
        $gone = $this->verified('deadline_main', '13 January 2027', null, 'https://www.ucas.com/old');
        $flaky = $this->verified('deadline_medicine', '15 October 2026', null, 'https://www.ucas.com/flaky');
        Http::fake(['https://www.ucas.com/old' => Http::response('', 404), 'https://www.ucas.com/flaky' => Http::response('', 503)]);

        $this->artisan('smukn:sources-check')->expectsOutputToContain('1 unreachable')->assertSuccessful();
        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $gone->fresh()->verification_status);
        $this->assertSame(ReferenceFact::VERIFIED, $flaky->fresh()->verification_status);
    }

    public function test_a_dry_run_changes_nothing_and_unverified_facts_are_not_fetched(): void
    {
        $fact = $this->verified('deadline_main', '13 January 2027', null, 'https://www.ucas.com/old');
        Topic::bySlug('ucas-2027')->facts()->create(['key' => 'other', 'value_text' => 'x', 'source_url' => 'https://example.org/unverified', 'source_type' => 'official', 'verification_status' => ReferenceFact::VERIFY_ON_PAGE]);
        Http::fake(['https://www.ucas.com/old' => Http::response('', 404), '*' => Http::response('should not be fetched', 200)]);

        $this->artisan('smukn:sources-check', ['--dry-run' => true])->expectsOutputToContain('[dry run] 1 page(s) checked: 1 fact(s) un-verified')->assertSuccessful();
        $this->assertSame(ReferenceFact::VERIFIED, $fact->fresh()->verification_status);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'example.org'));
        $this->assertDatabaseCount('source_snapshots', 0);
    }
}
