<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\UniversityPublicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * data/seo/decision-register.csv is the SEO decision engine (docs/seo/DECISION-ENGINE.md). These tests keep it honest:
 * every indexable URL has a decided row, every live row points at a live indexable page, drafts stay noindex.
 */
class SeoDecisionRegisterTest extends TestCase
{
    use RefreshDatabase;

    private const STATUSES = ['RESEARCH', 'VALIDATED', 'BUILD', 'DRAFT', 'REVIEW', 'PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE', 'REJECTED'];

    private const LIVE = ['PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE'];

    /** @return array<int, array<string, string>> */
    private function rows(): array
    {
        $h = fopen(base_path('data/seo/decision-register.csv'), 'r');
        $header = fgetcsv($h);
        $rows = [];
        while (($r = fgetcsv($h)) !== false) {
            $rows[] = array_combine($header, $r);
        }
        fclose($h);

        return $rows;
    }

    public function test_every_row_has_a_known_status_and_a_reason(): void
    {
        $rows = $this->rows();
        $this->assertGreaterThan(40, count($rows));
        $ids = [];
        foreach ($rows as $r) {
            $this->assertContains($r['status'], self::STATUSES, "{$r['id']} has an unknown status {$r['status']}");
            $this->assertGreaterThan(20, mb_strlen($r['status_reason']), "{$r['id']} needs a recorded reason");
            $this->assertNotContains($r['id'], $ids, "duplicate id {$r['id']}");
            $ids[] = $r['id'];
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $r['decided_on']);
            // Semrush figures are either imported numbers or an explicit DATA UNAVAILABLE marker, never a guess.
            foreach (['volume', 'keyword_difficulty'] as $col) {
                $this->assertTrue(is_numeric($r[$col]) || str_starts_with($r[$col], 'DATA UNAVAILABLE'), "{$r['id']} {$col} must be numeric or DATA UNAVAILABLE");
            }
        }
    }

    public function test_every_sitemap_url_has_a_live_decision_row(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
        $live = collect($this->rows())->whereIn('status', self::LIVE)->map(fn ($r) => strtok($r['current_url'], '#'))->unique()->all();
        foreach ($m[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $this->assertContains($path, $live, "$path is in the sitemap without a PUBLISHED/UPDATE row in data/seo/decision-register.csv");
        }
    }

    public function test_live_rows_point_at_indexable_pages_and_drafts_stay_noindex(): void
    {
        $this->seed([ReferenceDataSeeder::class, UniversityPublicationSeeder::class]); // live university rows need their schools
        foreach ($this->rows() as $r) {
            $path = strtok($r['current_url'], '#');
            if ($path === '—' || $path === '' || str_contains($path, '{') || str_contains($path, '?')) {
                continue;
            }
            $html = $this->get($path)->assertOk()->getContent();
            if (in_array($r['status'], self::LIVE, true)) {
                $this->assertStringNotContainsString('noindex', $html, "{$r['id']} is live but $path is noindex");
            } elseif ($r['status'] === 'DRAFT') {
                $this->assertStringContainsString('noindex', $html, "{$r['id']} is DRAFT but $path is indexable; move it to PUBLISHED");
            }
            if (($frag = strstr($r['current_url'], '#')) && str_starts_with($frag, '#q')) {
                $this->assertStringContainsString('id="'.substr($frag, 1).'"', $html, "{$r['id']} points at a missing anchor {$r['current_url']}");
            }
        }
    }

    public function test_core_landing_page_is_linked_from_the_home_page_every_hub_and_the_faq(): void
    {
        $target = route('medicine.nigeria');
        foreach (['/', '/study-medicine-in-the-uk', '/requirements', '/fees', '/admissions', '/medical-schools', '/faq'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));
            $this->assertStringContainsString('href="'.$target.'"', $main, "$path must link to the core landing page inside <main>");
        }
    }

    public function test_every_live_informational_page_offers_a_next_step_towards_eligibility_or_apply_online(): void
    {
        // Search-to-action chain (DECISION-ENGINE.md rule 6): trust and legal pages (cluster T) and the apply pages themselves are exempt.
        $targets = [route('apply.eligibility'), route('apply.index'), route('register')];
        $this->seed([ReferenceDataSeeder::class, UniversityPublicationSeeder::class]);
        foreach ($this->rows() as $r) {
            $path = strtok($r['current_url'], '#');
            if (! in_array($r['status'], self::LIVE, true) || $r['cluster'] === 'T' || $r['cluster'] === 'A' || str_contains($path, '{') || str_contains($path, '?') || $path === '—') {
                continue;
            }
            $html = $this->get($path)->assertOk()->getContent();
            $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));
            $found = collect($targets)->contains(fn ($t) => str_contains($main, 'href="'.$t.'"'));
            $this->assertTrue($found, "{$r['id']} $path has no in-body link to the eligibility check or Apply Online");
        }
    }

    public function test_directory_emits_an_item_list_and_hubs_carry_faq_schema_where_questions_are_visible(): void
    {
        $this->get('/medical-schools')->assertOk()->assertSee('"@type":"ItemList"', false);
        $this->get('/medical-schools?nation=Wales')->assertOk()->assertDontSee('"@type":"ItemList"', false)->assertSee('noindex', false);
        $this->get('/requirements/neco')->assertOk()->assertSee('"@type":"FAQPage"', false)->assertSee('id="q3"', false);
        $this->get('/requirements/nigerian-degree-graduate-entry')->assertOk()->assertSee('"@type":"FAQPage"', false)->assertSee('id="q31"', false);
        $this->assertNull($this->get('/')->headers->get('X-Powered-By'));
    }

    public function test_staff_can_read_the_register_in_the_admin_with_a_status_filter(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'staff', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $as = $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id]);
        $as->get('/admin/seo')->assertOk()->assertSee('SEO decisions')->assertSee('REJECTED')->assertSee('decision-register.csv');
        $as->get('/admin/seo?status=rejected')->assertOk()->assertSee('No defensible methodology')->assertDontSee('Primary asset.');
    }
}
