<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\University;
use App\Models\User;
use App\Services\Search\SearchInsights;
use App\Support\Sitemap;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The read-only Search Console feed (smukn:gsc-sync), its analysis, Admin → Search and the public weekly report. */
class SearchConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function connect(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        config(['services.gsc.service_account' => base64_encode(json_encode(['client_email' => 'reader@project.iam.gserviceaccount.com', 'private_key' => $pem])),
            'services.gsc.property' => 'sc-domain:studymedicineuknigeria.com']);
        Cache::forget('gsc.access_token');
    }

    private function row(string $date, string $query, string $page, string $country, int $clicks, int $impr, float $pos): void
    {
        DB::table('search_performance')->insert(['date' => $date, 'query' => $query, 'page' => $page, 'country' => $country, 'row_hash' => sha1("$date $query $page $country"),
            'clicks' => $clicks, 'impressions' => $impr, 'position' => $pos]);
    }

    public function test_nothing_is_fetched_until_the_owner_connects_a_service_account(): void
    {
        Http::fake();
        config(['services.gsc.service_account' => null]);
        $this->artisan('smukn:gsc-sync')->expectsOutputToContain('not connected')->assertSuccessful();
        Http::assertNothingSent();
        $this->artisan('smukn:search-report')->expectsOutputToContain('not connected')->assertSuccessful();
    }

    public function test_sync_signs_in_read_only_stores_rows_and_records_googles_index_verdict(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $this->connect();
        $home = url('/');
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'www.googleapis.com/webmasters/*' => Http::response(['rows' => [
                ['keys' => ['2026-10-01', 'study medicine in uk from nigeria', $home, 'nga'], 'clicks' => 3, 'impressions' => 120, 'ctr' => 0.025, 'position' => 8.4],
                ['keys' => ['2026-10-01', 'uk medical school fees', url('/fees'), 'gbr'], 'clicks' => 0, 'impressions' => 40, 'ctr' => 0, 'position' => 22.1],
            ]]),
            'searchconsole.googleapis.com/*' => fn (Request $r) => Http::response(['inspectionResult' => ['indexStatusResult' => $r['inspectionUrl'] === $home
                ? ['verdict' => 'PASS', 'coverageState' => 'Submitted and indexed', 'googleCanonical' => $home, 'lastCrawlTime' => '2026-10-05T10:00:00Z']
                : ['verdict' => 'NEUTRAL', 'coverageState' => 'Discovered - currently not indexed']]]),
        ]);

        $this->artisan('smukn:gsc-sync', ['--days' => 5])->assertSuccessful();
        $this->artisan('smukn:gsc-sync', ['--days' => 5])->assertSuccessful(); // re-reading the same days updates, never duplicates

        $this->assertSame(2, DB::table('search_performance')->count());
        $this->assertSame(count(Sitemap::urls()), DB::table('search_index_status')->count(), 'every sitemap URL is inspected');
        $this->assertSame('PASS', DB::table('search_index_status')->where('url', $home)->value('verdict'));
        $this->assertSame('Discovered - currently not indexed', DB::table('search_index_status')->where('url', url('/fees'))->value('coverage_state'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'oauth2.googleapis.com') && str_contains(base64_decode(strtr(explode('.', $r['assertion'])[1], '-_', '+/')), 'webmasters.readonly'));
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'sitemaps') || $r->method() === 'PUT' || $r->method() === 'DELETE');
        $this->assertNotNull(Cache::get('gsc.last_sync'));
    }

    public function test_a_google_error_fails_the_run_without_leaking_the_token(): void
    {
        $this->connect();
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['access_token' => 'secret-token-value']), 'www.googleapis.com/*' => Http::response(['error' => ['message' => 'User does not have sufficient permission for site']], 403)]);
        $this->artisan('smukn:gsc-sync')->expectsOutputToContain('sufficient permission')->doesntExpectOutputToContain('secret-token-value')->assertFailed();
    }

    public function test_insights_find_nigerian_queries_low_ctr_split_queries_and_school_demand(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $school = University::medicalSchools()->where('published', false)->orderBy('name')->firstOrFail();
        $name = SearchInsights::schoolNames($school)[0];
        $base = 'https://studymedicineuknigeria.com';
        $this->row('2026-09-01', 'old query', "$base/fees", 'nga', 1, 30, 9); // previous period, so not "new"
        foreach (['2026-09-20', '2026-09-28'] as $d) {
            $this->row($d, 'study medicine in uk from nigeria', "$base/", 'nga', 1, 150, 6);
            $this->row($d, 'study medicine in uk from nigeria', "$base/study-medicine-in-the-uk", 'nga', 0, 20, 12);
            $this->row($d, "$name medicine international", "$base/medical-schools", 'nga', 0, 15, 30);
        }
        $this->row('2026-09-28', 'ucat test centre lagos', "$base/admissions/ucat", 'nga', 0, 5, 14);

        $in = new SearchInsights(28);
        $this->assertSame('study medicine in uk from nigeria', $in->nigeriaQueries()->first()['query']);
        $this->assertSame("$base/", $in->lowCtrPages()->first()['page'], '300 impressions at position 6 with under 2% CTR');
        $this->assertSame('study medicine in uk from nigeria', $in->cannibalisation()->first()['query']);
        $this->assertSame($school->name, $in->schoolDemand()->first()['school']);
        $this->assertSame(30, $in->schoolDemand()->first()['impressions']);
        $this->assertSame(['ucat test centre lagos'], $in->newQueries()->pluck('query')->all(), 'only a query first seen in the last 7 days');
        $this->assertSame(6.0, $in->lowCtrPages()->first()['position'], 'position is weighted by impressions');
    }

    public function test_admin_search_page_is_staff_only_and_the_public_report_hides_small_queries(): void
    {
        $this->row(now()->subDays(3)->toDateString(), 'big query', 'https://studymedicineuknigeria.com/', 'nga', 2, 80, 5);
        $this->row(now()->subDays(3)->toDateString(), 'someone rare query', 'https://studymedicineuknigeria.com/fees', 'nga', 0, 3, 40);

        $this->get('/admin/search')->assertRedirect();
        $student = User::factory()->create();
        $this->actingAs($student)->get('/admin/search')->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'staff', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->get('/admin/search')
            ->assertOk()->assertSee('Queries from Nigeria')->assertSee('big query')->assertSee('someone rare query')->assertSee('Not connected');

        $this->artisan('smukn:search-report')->expectsOutputToContain('"big query"')->doesntExpectOutputToContain('someone rare query')->assertSuccessful();
    }
}
