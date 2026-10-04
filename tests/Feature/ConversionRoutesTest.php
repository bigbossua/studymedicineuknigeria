<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * A reader who finishes any indexable page meets the next step inside the content itself: the eligibility check and
 * Apply Online (the header, footer and floating button do not count). Legal pages state terms and carry no sales
 * routes by design. Audit: docs/seo/PAGE-AUDIT.md (ops/seo/page-audit.py).
 */
class ConversionRoutesTest extends TestCase
{
    use RefreshDatabase;

    private const LEGAL = ['/privacy', '/terms', '/application-terms', '/refund-policy'];

    public function test_every_indexable_page_body_routes_to_eligibility_and_apply_online(): void
    {
        Artisan::call('smukn:reference-sync');
        preg_match_all('#<loc>([^<]+)</loc>#', $this->get('/sitemap.xml')->assertOk()->getContent(), $m);
        $gated = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => isset($r->defaults['sitemap']['gate']))->map(fn ($r) => url($r->uri()))->all();
        $eligibility = route('apply.eligibility');
        $apply = route('apply.index');
        $checked = 0;
        foreach (array_unique(array_merge($m[1], $gated)) as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            if (in_array($path, self::LEGAL, true)) {
                continue;
            }
            $html = $this->get($path)->assertOk()->getContent();
            $main = str_contains($html, '<main') ? explode('</main>', explode('<main', $html, 2)[1], 2)[0] : $html;
            if ($path !== '/apply-online/eligibility') {
                $this->assertStringContainsString('href="'.$eligibility.'"', $main, "{$path}: no in-body route to the eligibility check");
            }
            if ($path !== '/apply-online') {
                $this->assertMatchesRegularExpression('#href="'.preg_quote($apply, '#').'(/(?!eligibility")[^"]*)?"#', $main, "{$path}: no in-body route to Apply Online");
            }
            $checked++;
        }
        $this->assertGreaterThan(20, $checked);
    }

    public function test_floating_whatsapp_contact_is_secondary_to_apply_online_and_carries_no_personal_data(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $aside = explode('</aside>', explode('data-floating-cta', $html, 2)[1], 2)[0];
        $text = rawurlencode('Hello, I would like help studying Medicine in the UK from Nigeria.');
        $this->assertStringContainsString('href="https://wa.me/447842292527?text='.$text.'"', $aside);
        $this->assertStringContainsString('WhatsApp us', $aside);
        $this->assertStringContainsString('role="tooltip"', $aside);
        $this->assertStringContainsString('Questions about studying Medicine in the UK?', $aside);
        // Apply Online stays in the same corner, as the primary button
        $this->assertMatchesRegularExpression('#class="btn btn-primary[^"]*">Apply Online</a>#', $aside);
        // the apply route itself keeps WhatsApp but not a second Apply Online button
        foreach (['/apply-online', '/apply-online/eligibility'] as $path) {
            $fab = explode('</aside>', explode('data-floating-cta', $this->get($path)->getContent(), 2)[1], 2)[0];
            $this->assertStringContainsString('wa.me/447842292527', $fab, $path);
            $this->assertStringNotContainsString('Apply Online</a>', $fab, $path);
        }
        // the sign-in pages keep their screen free of floating buttons
        $this->assertStringNotContainsString('data-floating-cta', $this->get('/login')->getContent());
        config(['site.whatsapp' => '']);
        $this->assertStringNotContainsString('wa.me/', explode('</aside>', explode('data-floating-cta', $this->get('/')->getContent(), 2)[1], 2)[0]);
    }
}
