<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Rollback safety (2026-10-30): a code rollback must not leave the previous release serving facts it was never deployed
 * with. On a production mirror, rolling back without undoing the release's fact decisions would have indexed eleven more
 * pages under the old indexing rule. smukn:facts-revert restores exactly the facts one import changed (from
 * fact_changes), keeps any fact changed since, and removes the file's ledger row so a redeploy applies it again.
 */
class FactsRevertTest extends TestCase
{
    use RefreshDatabase;

    private string $dec = 'storage/framework/testing/decisions-revert.csv';

    protected function tearDown(): void
    {
        File::delete(base_path($this->dec));
        parent::tearDown();
    }

    public function test_a_revert_restores_the_imported_facts_keeps_later_changes_and_reopens_the_ledger(): void
    {
        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville', 'nation' => 'England', 'international_policy' => 'accepts']);
        $c = Course::create(['university_id' => $u->id, 'slug' => 'a100', 'title' => 'Medicine MBBS', 'entry_type' => 'standard']);
        $route = $c->facts()->create(['key' => 'application_route', 'value_text' => 'UCAS', 'verification_status' => 'SOURCE_CHANGED', 'source_url' => 'https://example.ac.uk/apply', 'verified_at' => '2026-10-05', 'source_type' => 'official']);
        $fee = $c->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 50000, 'academic_year' => '2027/28', 'verification_status' => 'SOURCE_CHANGED', 'source_url' => 'https://example.ac.uk/fees', 'source_type' => 'official']);
        $english = $u->facts()->create(['key' => 'english_requirement', 'value_text' => 'IELTS 7.0', 'verification_status' => 'SOURCE_CHANGED', 'source_url' => 'https://example.ac.uk/english', 'source_type' => 'official']);
        $before = ReferenceFact::orderBy('id')->get(['id', 'verification_status', 'value_text', 'value_number', 'source_url', 'verified_at', 'review_due_at', 'notes'])->toArray();

        File::put(base_path($this->dec), "ref,decision,verified_value,new_source_url,reviewer_note,verified_on\n".
            "course:testville/a100:application_route,verified,\"UCAS (code A100)\",https://example.ac.uk/apply-now,Re-read,2026-10-29\n".
            "course:testville/a100:international_fee_gbp:2027/28,verified,51000,,Re-read,2026-10-29\n".
            "university:testville:english_requirement,verified,,,Re-read,2026-10-29\n");
        $this->artisan('smukn:facts-import', ['file' => $this->dec])->expectsOutputToContain('3 fact(s) updated')->assertSuccessful();
        $this->assertSame('VERIFIED', $route->fresh()->verification_status);
        $this->assertSame(1, DB::table('fact_imports')->count());

        // after the deploy, the source watcher flags the English requirement again: that newer state must survive a revert
        $english->fresh()->update(['verification_status' => 'SOURCE_CHANGED']);

        $this->artisan('smukn:facts-revert', ['file' => $this->dec, '--dry-run' => true])->expectsOutputToContain('[dry run] 2 fact(s) restored, 1 kept')->assertSuccessful();
        $this->assertSame('VERIFIED', $route->fresh()->verification_status, 'a dry run changes nothing');

        $this->artisan('smukn:facts-revert', ['file' => $this->dec])->expectsOutputToContain('2 fact(s) restored, 1 kept')->assertSuccessful();
        $after = ReferenceFact::orderBy('id')->get(['id', 'verification_status', 'value_text', 'value_number', 'source_url', 'verified_at', 'review_due_at', 'notes'])->toArray();
        $this->assertSame($before[0], $after[0], 'route: status, value, source, dates and notes as before the import');
        $this->assertSame($before[1], $after[1], 'fee: the number comes back too');
        $this->assertSame('SOURCE_CHANGED', $english->fresh()->verification_status, 'a later change is kept');
        $this->assertSame(0, DB::table('fact_imports')->count(), 'the ledger row is removed so a redeploy applies the file again');
        $this->assertSame(2, DB::table('fact_changes')->where('via', 'like', 'worksheet revert %')->count(), 'every revert is audited');

        // a redeploy after the rollback applies the same decisions again (all three, as the file is applied afresh)
        $this->artisan('smukn:facts-import', ['file' => $this->dec])->expectsOutputToContain('3 fact(s) updated')->assertSuccessful();
        $this->assertSame('VERIFIED', $fee->fresh()->verification_status);
        $this->assertEquals(51000, $fee->fresh()->value_number);
    }

    public function test_a_file_that_was_never_imported_changes_nothing(): void
    {
        File::put(base_path($this->dec), "ref,decision,verified_on\n");
        $this->artisan('smukn:facts-revert', ['file' => $this->dec])->expectsOutputToContain('0 fact(s) restored, 0 kept')->assertSuccessful();
    }
}
