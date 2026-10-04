<?php

namespace Tests\Feature;

use App\Console\Commands\FactsEvidence;
use App\Models\ReferenceFact;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The evidence pre-check reads official pages and reports; it never verifies or changes a fact. */
class FactsEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_matches_mismatches_and_dead_pages_without_changing_any_fact(): void
    {
        $this->seed(TopicFactsSeeder::class);
        $deadline = ReferenceFact::where('key', 'deadline_medicine')->firstOrFail();
        $statuses = ReferenceFact::pluck('verification_status', 'id');
        $source = $deadline->source_url;
        Http::fake(function ($request) use ($source) {
            return match (true) {
                $request->url() === $source => Http::response('<html><body><h2>Key dates</h2><p>Medicine, dentistry and veterinary courses: <b>15 October 2026</b> at 18:00 (UK time).</p></body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']),
                default => Http::response('gone', 404, ['Content-Type' => 'text/html']),
            };
        });

        $file = tempnam(sys_get_temp_dir(), 'ev');
        $this->artisan('smukn:facts-evidence', ['file' => $file, '--max-priority' => 2])->assertSuccessful();
        $rows = array_map('str_getcsv', file($file, FILE_IGNORE_NEW_LINES));
        $head = array_shift($rows);
        $byKey = collect($rows)->map(fn ($r) => array_combine($head, $r))->keyBy('key');

        $this->assertSame('exact', $byKey['deadline_medicine']['match']);
        $this->assertStringContainsString('15 October 2026', $byKey['deadline_medicine']['evidence']);
        $this->assertSame('fetch_failed', $byKey->first(fn ($r) => $r['source_url'] !== $source)['match'] ?? 'fetch_failed');
        // nothing was verified or changed
        $this->assertEquals($statuses, ReferenceFact::pluck('verification_status', 'id'));
    }

    public function test_figures_must_all_be_on_the_page_for_an_exact_match(): void
    {
        $f = new ReferenceFact(['key' => 'deadline_main', 'value_text' => '13 January 2027, 18:00 (UK time)']);
        $this->assertSame('exact', FactsEvidence::find($f, 'Equal consideration date: 13 January 2027 at 18:00')[0]);
        [$match, $evidence] = FactsEvidence::find($f, 'Equal consideration date: 14 January 2027 at 18:00');
        $this->assertSame('partial', $match);
        $this->assertStringContainsString('missing 13 January 2027', $evidence);
        $this->assertSame('none', FactsEvidence::find($f, 'Nothing about dates here')[0]);
        // official pages often abbreviate and drop the year: "13 Jan (18:00 UK time)"
        $this->assertSame('exact', FactsEvidence::find($f, 'Equal consideration date 13 Jan (18:00 UK time)')[0]);
    }
}
