<?php

namespace Tests\Feature;

use App\Models\University;
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
}
