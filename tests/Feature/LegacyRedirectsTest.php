<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every URL in the previous site's sitemap (read by the public launch checks, 2026-10-04) keeps working after the
 * switch: a 301 straight to the equivalent page that answers 200, the same path where the page exists, or a 404 when
 * nothing equivalent exists (never a redirect to an unrelated page).
 */
class LegacyRedirectsTest extends TestCase
{
    use RefreshDatabase;

    private const SAME_PATH = ['/', '/about', '/contact', '/faq'];

    public function test_every_previous_site_url_has_a_working_answer(): void
    {
        Artisan::call('smukn:reference-sync');
        $h = fopen(base_path('data/seo/legacy-redirects.csv'), 'r');
        fgetcsv($h, null, ',', '"', '');
        $n = 0;
        while (($row = fgetcsv($h, null, ',', '"', '')) !== false) {
            [$from, $to] = $row;
            // the previous site's own spelling, mixed case included, goes straight to the target in one hop
            $asListed = preg_replace('#^/study-medicine-in-#', '/Study-medicine-in-', preg_replace('#/service-areas/study-#', '/service-areas/Study-', $from));
            $this->get($asListed)->assertStatus(301)->assertRedirect($to);
            $this->get($to)->assertOk();
            $n++;
        }
        fclose($h);
        $this->assertSame(65, $n);
        foreach (self::SAME_PATH as $path) {
            $this->get($path)->assertOk();
        }
        foreach (array_filter(file(base_path('data/seo/legacy-gone.txt'), FILE_IGNORE_NEW_LINES), fn ($l) => str_starts_with($l, '/')) as $gone) {
            $this->get($gone)->assertNotFound();
        }
    }

    public function test_a_redirect_changed_in_admin_is_never_overwritten_by_a_deploy(): void
    {
        Artisan::call('smukn:reference-sync');
        DB::table('redirects')->where('from_path', '/services')->update(['to_path' => '/apply-online', 'active' => true]);
        Artisan::call('smukn:reference-sync');
        $this->assertSame('/apply-online', DB::table('redirects')->where('from_path', '/services')->value('to_path'));
        $this->assertSame(65, DB::table('redirects')->count());
    }
}
