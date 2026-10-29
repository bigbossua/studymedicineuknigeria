<?php

namespace Tests\Feature;

use App\Console\Commands\CheckSources;
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

    public function test_a_reviewers_bracketed_remark_inside_the_quote_is_not_required_on_the_page(): void
    {
        $fact = $this->fact('A106 (2027 entry; institution code M20)', 'Apply through UCAS ⏎ UCAS course code: A106 / Institution code M20 [our earlier A100 came from a secondary source]');
        $page = '<p>Apply through UCAS</p><p>UCAS course code: A106 / Institution code M20</p>';
        $this->watch($page, $page.'<p>Open day 14 November</p>');

        $this->assertSame(ReferenceFact::VERIFIED, $fact->fresh()->verification_status);
    }

    public function test_a_bot_check_served_instead_of_the_page_flags_nothing(): void
    {
        // Regression for 2026-10-29: Buckingham answered 200 with a "Bot Verification" page and two verified facts were
        // flagged against that text. A challenge page is now an unreachable source, not a change of content.
        $fact = $this->fact('Leeds gives a mean of £198 a week.', 'These figures are a guideline, not a quote. ⏎ mean average £198 (£122 to £273)');
        $page = '<p>These figures are a guideline, not a quote.</p><td>mean average £198 (£122 to £273)</td>';
        $this->watch($page, '<html><head><title>Bot Verification</title></head><body><p>Verifying that you are not a robot...</p></body></html>');

        $this->assertSame(ReferenceFact::VERIFIED, $fact->fresh()->verification_status);
        $this->assertSame(hash('sha256', CheckSources::normalise($page)), \DB::table('source_snapshots')->value('content_hash'), 'the last real fingerprint is kept');
    }

    public function test_cloudflare_and_captcha_challenges_are_recognised_but_a_real_page_is_not(): void
    {
        $c = CheckSources::class;
        $this->assertTrue($c::isChallenge('<title>Just a moment...</title><div id="challenge-platform"></div>', 'just a moment...'));
        $this->assertTrue($c::isChallenge('<title>Human Verification</title>', 'human verification'));
        $this->assertTrue($c::isChallenge('<html><body><div class="h-captcha"></div></body></html>', 'please complete the check'));
        $long = str_repeat('Applications will not be considered unless all areas of the UCAS form have been completed. ', 30);
        $this->assertFalse($c::isChallenge('<title>Medicine (MBChB) | University</title><main>'.$long.'</main>', mb_strtolower($long)));
        $this->assertFalse($c::isChallenge('<title>Medicine</title><main>'.$long.'<div class="g-recaptcha"></div></main>', mb_strtolower($long)), 'a real page with a form captcha is still a page');
    }

    public function test_a_real_change_after_a_challenge_is_still_caught(): void
    {
        $fact = $this->fact('Leeds gives a mean of £198 a week.', 'These figures are a guideline, not a quote. ⏎ mean average £198 (£122 to £273)');
        Notification::fake();
        Http::fake([self::URL => Http::sequence()
            ->push('<p>These figures are a guideline, not a quote.</p><td>mean average £198 (£122 to £273)</td>')
            ->push('<title>Just a moment...</title><div id="challenge-platform"></div>')
            ->push('<p>These figures are a guideline, not a quote.</p><td>mean average £215 (£130 to £290)</td>')]);
        foreach (range(1, 3) as $i) {
            $this->artisan('smukn:sources-check')->assertSuccessful();
        }

        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $fact->fresh()->verification_status);
    }
}
