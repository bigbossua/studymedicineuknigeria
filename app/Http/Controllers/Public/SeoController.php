<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PublishGate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /portal/',
            'Disallow: /admin/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password/',
            'Disallow: /*?*',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];
        if (! app()->isProduction()) {
            $lines = ['User-agent: *', 'Disallow: /'];
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Only published, indexable routes are listed. Routes carry a 'sitemap' default with lastmod.
     */
    public function sitemap(): Response
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $meta = $route->defaults['sitemap'] ?? null;
            if (! $meta || ! in_array('GET', $route->methods(), true) || ! PublishGate::passes($meta['gate'] ?? null)) {
                continue;
            }
            $urls[] = [
                'loc' => url($route->uri() === '/' ? '/' : '/'.trim($route->uri(), '/')),
                'lastmod' => $meta['lastmod'] ?? null,
                'changefreq' => $meta['changefreq'] ?? 'monthly',
            ];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>".e($u['loc'])."</loc>\n";
            if ($u['lastmod']) {
                $xml .= '    <lastmod>'.e($u['lastmod'])."</lastmod>\n";
            }
            $xml .= '    <changefreq>'.e($u['changefreq'])."</changefreq>\n  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
