<?php

namespace Tests\Feature;

use App\Models\ReferenceFact;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * smukn:sources-check (nightly). Regression for October 2026: a verified value written as the reviewer's summary never
 * appears verbatim on the official page, so the first harmless change to that page un-verified it and took
 * /fees/cost-of-studying-medicine-in-the-uk and /working-in-the-uk out of the index. The recorded official quote now
 * decides; a real change to that wording still un-verifies the fact.
 */
class SourceWatcherTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://www.example.ac.uk/living-expenses';

    private function fact(string $value, string $quote): ReferenceFact
    {
        $topic = Topic::create(['slug' => 'costs-test', 'title' => 'Costs']);

        return ReferenceFact::create(['subject_type' => Topic::class, 'subject_id' => $topic->id, 'key' => 'living_costs_note', 'value_text' => $value,
            'source_url' => self::URL, 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(),
            'notes' => '[Review 2026-10-06: One official example. AI-assisted review of the official page; quote: "'.$quote.'"]']);
    }

    private function watch(string $first, string $second): void
    {
        Notification::fake();
        Http::fake([self::URL => Http::sequence()->push($first)->push($second)]);
        $this->artisan('smukn:sources-check')->assertSuccessful(); // first visit: fingerprint only
        $this->artisan('smukn:sources-check')->assertSuccessful();
    }

    public function test_a_summarised_value_stays_verified_while_its_official_quote_is_still_published(): void
    {
        $fact = $this->fact('Each university publishes its own estimate; Leeds gives a mean of £198 a week.', 'These figures are a guideline, not a quote. ⏎ mean average £198 (£122 to £273) ... adjusted for inflation for 2026/');
        $page = '<main><p>These figures are a guideline, not a quote.</p><td>mean average £198 (£122 to £273)</td><p>Figures adjusted for inflation for 2026/27.</p></main>';
        $this->watch($page, str_replace('<main>', '<main><div>Open day: 14 November</div>', $page));

        $this->assertSame(ReferenceFact::VERIFIED, $fact->fresh()->verification_status);
        $this->assertStringContainsString('official wording it was verified against is still on the page', $fact->fresh()->notes);
    }

    public function test_a_changed_official_wording_still_takes_the_fact_off_the_site(): void
    {
        $fact = $this->fact('Each university publishes its own estimate; Leeds gives a mean of £198 a week.', 'These figures are a guideline, not a quote. ⏎ mean average £198 (£122 to £273)');
        $this->watch('<p>These figures are a guideline, not a quote.</p><td>mean average £198 (£122 to £273)</td>',
            '<p>These figures are a guideline, not a quote.</p><td>mean average £215 (£130 to £290)</td>');

        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $fact->fresh()->verification_status);
    }

    public function test_a_fact_with_no_recorded_quote_is_judged_on_its_value_as_before(): void
    {
        $fact = $this->fact('x', 'y');
        $fact->forceFill(['notes' => null, 'value_text' => '£198 a week'])->save();
        $this->watch('<p>£198 a week</p>', '<p>£198 a week</p><p>Updated</p>');
        $this->assertSame(ReferenceFact::VERIFIED, $fact->fresh()->verification_status);
    }

    public function test_a_partly_changed_official_wording_goes_to_manual_review(): void
    {
        $fact = $this->fact('Leeds gives a mean of £198 a week for self-catered accommodation.', 'These figures are a guideline, not a quote. ⏎ mean average £198 (£122 to £273)');
        $this->watch('<p>These figures are a guideline, not a quote.</p><td>mean average £198 (£122 to £273)</td>',
            '<p>These figures are a guideline, not a quote.</p><td>mean average £204 (£125 to £280)</td>');

        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $fact->fresh()->verification_status, 'never kept verified on half the evidence');
        $this->assertStringContainsString('Manual review', $fact->fresh()->notes);
    }

    public function test_a_quote_cut_short_in_storage_is_read_to_its_end_and_later_notes_are_ignored(): void
    {
        $fact = $this->fact('Leeds gives a mean of £198 a week.', 'x');
        $fact->forceFill(['notes' => '[Review 2026-10-06: AI-assisted review; quote: "These figures are a guideline, not a quote. ⏎ mean average £198 (£122 to £273) ... adjusted for inflation for 2026/] [Source changed 2026-10-07; verified value still on the page]'])->save();
        $page = '<p>These figures are a guideline, not a quote.</p><td>mean average £198 (£122 to £273)</td><p>adjusted for inflation for 2026/27</p>';
        $this->watch($page, $page.'<p>New open day</p>');

        $this->assertSame(ReferenceFact::VERIFIED, $fact->fresh()->verification_status);
    }
}
