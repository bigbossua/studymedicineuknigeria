<?php

namespace Tests\Feature;

use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\University;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_canonical_robots_and_json_ld(): void
    {
        $this->get('/')->assertOk()->assertSee('<link rel="canonical"', false)->assertSee('application/ld+json', false)->assertSee('Study Medicine in the UK from Nigeria');
    }

    public function test_paths_are_canonicalised_to_lowercase_without_trailing_slash(): void
    {
        // Trailing-slash stripping is exercised by ops/smoke.sh against a real server; the HTTP test client normalises slashes itself.
        $this->get('/Requirements')->assertRedirect('/requirements');
        $this->get('/requirements/WAEC?x=1')->assertRedirect('/requirements/waec?x=1');
    }

    public function test_pending_pages_are_noindex_and_absent_from_the_sitemap(): void
    {
        // The total-cost page stays noindex until its inputs are verified (page asset register row 10)
        $this->get('/fees/cost-of-studying-medicine-in-the-uk')->assertOk()->assertSee('noindex, nofollow', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/fees/cost-of-studying-medicine-in-the-uk</loc>', false);
    }

    public function test_robots_disallows_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    }

    public function test_private_routes_carry_noindex_and_no_store_headers(): void
    {
        $r = $this->get('/login');
        $r->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
    }

    public function test_content_security_policy_is_enforced_with_a_per_response_nonce(): void
    {
        $r = $this->get('/')->assertOk()->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $csp = $r->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        preg_match("/'nonce-([^']+)'/", $csp, $m);
        $this->assertStringContainsString('nonce="'.$m[1].'"', $r->getContent(), 'Vite tags must carry the response nonce');
        $this->assertDoesNotMatchRegularExpression('/\son(click|submit|load|change)=/i', $r->getContent(), 'no inline event handlers');
        $this->assertNotSame($m[1], $this->get('/')->headers->get('Content-Security-Policy'), 'nonce changes per response');
    }

    public function test_every_sitemap_page_fits_search_snippets_and_has_one_h1(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $html = $this->get($path)->assertOk()->getContent();
            preg_match('#<title>(.*?)</title>#s', $html, $t);
            preg_match('#<meta name="description" content="([^"]*)"#', $html, $d);
            $title = html_entity_decode($t[1] ?? '');
            $desc = html_entity_decode($d[1] ?? '');
            $this->assertLessThanOrEqual(65, mb_strlen($title), "$path title too long: $title");
            $this->assertGreaterThanOrEqual(25, mb_strlen($title), "$path title too short: $title");
            $this->assertLessThanOrEqual(165, mb_strlen($desc), "$path description too long (".mb_strlen($desc).')');
            $this->assertGreaterThanOrEqual(100, mb_strlen($desc), "$path description too short: $desc");
            $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html), "$path must have exactly one h1");
        }
    }

    public function test_fact_gated_pages_enter_the_index_only_when_their_topics_are_verified(): void
    {
        $this->seed(TopicFactsSeeder::class);
        $this->get('/working-in-the-uk')->assertOk()->assertSee('name="robots" content="noindex', false)->assertSee('Verification in progress');
        $this->assertStringNotContainsString('/working-in-the-uk', $this->get('/sitemap.xml')->getContent());
        $this->assertStringNotContainsString('/fees/cost-of-studying-medicine-in-the-uk', $this->get('/sitemap.xml')->getContent());

        $topics = Topic::whereIn('slug', ['student-visa', 'graduate-visa', 'gmc-registration'])->pluck('id');
        ReferenceFact::where('subject_type', Topic::class)->whereIn('subject_id', $topics)->where('verification_status', ReferenceFact::NOT_FOUND)->update(['verification_status' => ReferenceFact::NOT_PUBLISHED]);
        ReferenceFact::where('subject_type', Topic::class)->whereIn('subject_id', $topics)->where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->update(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);

        $this->get('/working-in-the-uk')->assertOk()->assertSee('name="robots" content="index, follow', false)->assertDontSee('Verification in progress');
        $this->assertStringContainsString('/working-in-the-uk', $this->get('/sitemap.xml')->getContent());
        $this->assertStringNotContainsString('/fees/cost-of-studying-medicine-in-the-uk', $this->get('/sitemap.xml')->getContent(), 'cost page still gated on costs-2026');
    }

    public function test_error_pages_render_branded_and_noindex(): void
    {
        foreach (['419' => 'Your session expired', '429' => 'Too many requests', '500' => 'Something went wrong on our side', '503' => 'We are updating the site'] as $code => $heading) {
            $html = view("errors.$code")->render();
            $this->assertStringContainsString("<h1>$heading</h1>", $html, $code);
            $this->assertStringContainsString('name="robots" content="noindex', $html, $code);
            $this->assertStringContainsString(config('site.email'), $html, $code);
        }
    }

    public function test_whatsapp_links_appear_only_when_a_number_is_configured(): void
    {
        config(['site.whatsapp' => null]);
        $this->get('/apply-online')->assertOk()->assertDontSee('wa.me', false);
        config(['site.whatsapp' => '2348000000000']);
        $this->get('/apply-online')->assertOk()->assertSee('https://wa.me/2348000000000', false);
        $this->get('/contact')->assertOk()->assertSee('https://wa.me/2348000000000', false);
    }

    public function test_unknown_pages_return_the_branded_404(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('We could not find that page');
    }

    public function test_university_pages_are_noindex_until_published_and_honest_about_missing_statements(): void
    {
        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville', 'nation' => 'England', 'international_policy' => 'accepts', 'published' => false]);
        $this->get('/medical-schools/testville')->assertOk()->assertSee('noindex', false)->assertSee('No Nigeria-specific statement located');
        $u->update(['published' => true]);
        $this->get('/medical-schools/testville')->assertOk()->assertSee('index, follow', false);
    }

    public function test_no_unverified_fact_wording_reaches_any_public_page_in_production(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(TopicFactsSeeder::class);
        $this->app['env'] = 'production';
        config(['site.publish_unverified' => false]);
        // Distinctive wording of every unverified fact (short values such as "AAA" or dates would match ordinary prose, so only sentences count).
        $needles = ReferenceFact::where('verification_status', '!=', ReferenceFact::VERIFIED)->where('key', '!=', 'ucas_code')->where('key', 'not like', 'source\_%')->pluck('value_text')->filter(fn ($v) => $v && mb_strlen($v) >= 40 && ! str_starts_with($v, 'http'))->unique()->values();
        $this->assertGreaterThan(100, $needles->count());
        preg_match_all('#<loc>([^<]+)</loc>#', $this->get('/sitemap.xml')->getContent(), $m);
        $paths = array_map(fn ($u) => parse_url($u, PHP_URL_PATH) ?: '/', $m[1]);
        $paths[] = '/medical-schools/'.University::where('international_policy', 'accepts')->first()->slug;
        $paths[] = '/medical-schools/'.University::where('international_policy', 'home_only')->first()->slug;
        $paths[] = '/fees/cost-of-studying-medicine-in-the-uk'; // gated pages are reachable even while noindex
        $paths[] = '/working-in-the-uk';
        foreach ($paths as $path) {
            $html = html_entity_decode($this->get($path)->assertOk()->getContent());
            foreach ($needles as $needle) {
                $this->assertStringNotContainsString($needle, $html, "$path shows unverified wording in production: ".mb_substr($needle, 0, 60));
            }
        }
    }
}
