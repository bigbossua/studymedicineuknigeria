<?php

namespace Tests\Feature;

use App\Models\University;
use App\Support\OgImage;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OgImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderer_produces_a_1200_by_630_png_and_wraps_long_titles(): void
    {
        $png = OgImage::render('A-levels for UK Medicine from Nigeria: grades, subjects and how international applicants are assessed by every school');
        $this->assertStringStartsWith("\x89PNG", $png);
        [$w, $h] = getimagesizefromstring($png);
        $this->assertSame([1200, 630], [$w, $h]);
    }

    public function test_command_builds_cards_for_sitemap_pages_and_pages_use_them(): void
    {
        $dest = storage_path('framework/testing/og');
        File::deleteDirectory($dest);
        $this->artisan('smukn:og', ['--dest' => str_replace(base_path().'/', '', $dest)])->expectsOutputToContain('og: home ←')->assertSuccessful();
        $this->assertFileExists("$dest/home.png");
        $this->assertFileExists("$dest/requirements.waec.png");
        $this->assertDatabaseCount('funnel_events', 0); // build requests are not visitors

        // Seo picks the generated card for the matching route name
        File::ensureDirectoryExists(public_path('images/og'));
        $live = public_path('images/og/home.png');
        $had = is_file($live);
        if (! $had) {
            File::copy("$dest/home.png", $live);
        }
        try {
            $this->get('/')->assertOk()->assertSee('/images/og/home.png"', false)->assertSee('og:image:alt', false);
        } finally {
            if (! $had) {
                File::delete($live);
            }
            File::deleteDirectory($dest);
        }
    }

    public function test_university_cards_render_on_demand_and_are_referenced_by_the_page(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $u = University::first();
        $this->get('/medical-schools/'.$u->slug)->assertOk()->assertSee('property="og:image" content="http://localhost:8000/images/og/schools/'.$u->slug.'.png"', false);
        $r = $this->get('/images/og/schools/'.$u->slug.'.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        [$w] = getimagesizefromstring($r->getContent());
        $this->assertSame(1200, $w);
        File::delete(public_path('images/og/schools/'.$u->slug.'.png'));
    }
}
