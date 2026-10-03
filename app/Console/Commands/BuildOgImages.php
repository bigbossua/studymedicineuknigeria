<?php

namespace App\Console\Commands;

use App\Support\OgImage;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Renders a branded Open Graph card for every published public page (routes with a 'sitemap' default)
 * into public/images/og/<route-name>.png, taking each page's og:title from the page itself.
 * University pages are rendered on demand by the schools.og route instead (see SchoolController).
 */
class BuildOgImages extends Command
{
    protected $signature = 'smukn:og {--dest=public/images/og : output folder}';

    protected $description = 'Generate per-page Open Graph images for the public pages';

    public function handle(): int
    {
        $dest = base_path($this->option('dest'));
        File::ensureDirectoryExists($dest);
        $n = 0;
        foreach (Route::getRoutes() as $route) {
            if (! isset($route->defaults['sitemap']) || ! in_array('GET', $route->methods(), true) || ! $route->getName()) {
                continue;
            }
            $request = Request::create(rtrim((string) config('app.url'), '/').'/'.ltrim($route->uri(), '/'), 'GET');
            $request->headers->set('X-SMUKN-Build', '1'); // no funnel events, no analytics during the build
            $response = app()->handle($request);
            if ($response->getStatusCode() !== 200 || ! preg_match('#<meta property="og:title" content="([^"]*)"#', $response->getContent(), $m)) {
                $this->warn("skipped {$route->getName()} (HTTP {$response->getStatusCode()})");

                continue;
            }
            $title = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            File::put("$dest/{$route->getName()}.png", OgImage::render($title));
            $n++;
            $this->line("og: {$route->getName()} ← {$title}");
        }
        $this->info("$n Open Graph image(s) written to {$this->option('dest')}");

        return self::SUCCESS;
    }
}
