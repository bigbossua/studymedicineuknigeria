<?php

namespace Tests\Feature;

use App\Models\ReferenceFact;
use App\Models\University;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** The rules behind docs/seo/GOOGLE-READINESS.md, kept true on every push. */
class GoogleReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @return list<array<string, mixed>> */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(fn ($j) => json_decode($j, true, 512, JSON_THROW_ON_ERROR), $m[1]);
    }

    public function test_robots_lets_google_read_the_noindex_of_linked_auth_pages_and_blocks_private_areas(): void
    {
        $this->app['env'] = 'production';
        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        foreach (['/portal/', '/admin', '/password/', '/email/', '/two-factor/', '/apply-online/start/', '/webhooks/'] as $private) {
            $this->assertStringContainsString("Disallow: {$private}\n", $robots);
        }
        // /login and /register are linked from every page: blocking them would stop Google seeing their noindex
        $this->assertStringNotContainsString("Disallow: /login\n", $robots);
        $this->assertStringNotContainsString("Disallow: /register\n", $robots);
        foreach (['/login', '/register'] as $auth) {
            $this->assertStringContainsString('noindex', $this->get($auth)->headers->get('X-Robots-Tag'));
        }
    }

    public function test_sitemap_lists_only_public_canonical_urls(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $loc) {
            $path = parse_url($loc, PHP_URL_PATH) ?? '/';
            $this->assertDoesNotMatchRegularExpression('#^/(login|register|portal|admin|password|email|two-factor|apply-online/start|webhooks)#', $path, $loc);
            $this->assertStringNotContainsString('?', $loc);
        }
    }

    public function test_structured_data_describes_the_site_honestly(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
        foreach ($m[1] as $loc) {
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $html = $this->get($path)->assertOk()->getContent();
            $blocks = $this->jsonLd($html);
            $types = array_column($blocks, '@type');
            $this->assertContains('Organization', $types, $path);
            $this->assertContains('WebPage', $types, $path);
            $raw = json_encode($blocks);
            foreach (['aggregateRating', 'Review', '"offers"', '"price"', 'SearchAction', 'alumni', 'numberOfStudents'] as $never) {
                $this->assertStringNotContainsString($never, $raw, "$path carries $never");
            }
            if ($path !== '/') {
                $crumbs = collect($blocks)->firstWhere('@type', 'BreadcrumbList');
                $this->assertNotNull($crumbs, $path);
                $this->assertSame('Home', $crumbs['itemListElement'][0]['name'], $path);
            }
            // FAQ markup only for questions printed on the page
            foreach (collect($blocks)->where('@type', 'FAQPage') as $faq) {
                foreach ($faq['mainEntity'] as $q) {
                    $this->assertStringContainsString(e($q['name']), $html, "$path FAQ question not visible: {$q['name']}");
                }
            }
        }
        // noindex pages carry no WebPage node
        $this->assertNotContains('WebPage', array_column($this->jsonLd($this->get('/login')->getContent()), '@type'));
    }

    public function test_search_console_tag_appears_only_on_the_home_page_when_configured(): void
    {
        $this->get('/')->assertDontSee('google-site-verification', false);
        config(['site.google_site_verification' => 'abc123-XYZ_token']);
        $this->get('/')->assertSee('<meta name="google-site-verification" content="abc123-XYZ_token">', false);
        $this->get('/fees')->assertDontSee('google-site-verification', false);
    }

    public function test_research_notes_never_reach_a_public_page(): void
    {
        Artisan::call('smukn:reference-sync');
        config(['site.publish_unverified' => true]); // the widest public view: unverified records shown too
        $notes = ReferenceFact::whereNotNull('notes')->where('notes', '!=', '')->pluck('notes')
            ->map(fn ($n) => e(mb_substr(trim($n), 0, 40)))->filter(fn ($n) => mb_strlen($n) >= 20)->unique();
        $this->assertNotEmpty($notes, 'the reference data carries reviewer notes to check against');
        preg_match_all('#<loc>([^<]+)</loc>#', $this->get('/sitemap.xml')->getContent(), $m);
        $paths = collect($m[1])->map(fn ($u) => parse_url($u, PHP_URL_PATH) ?: '/')
            ->merge(University::pluck('slug')->map(fn ($s) => '/medical-schools/'.$s));
        foreach ($paths as $path) {
            $html = $this->get($path)->getContent();
            foreach ($notes as $note) {
                $this->assertStringNotContainsString($note, $html, "$path shows a reviewer note");
            }
        }
    }
}
