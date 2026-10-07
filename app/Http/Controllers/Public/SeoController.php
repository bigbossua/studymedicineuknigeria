<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Sitemap;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        // Private areas are disallowed. /login and /register stay crawlable on purpose: they are linked from every page and
        // answer noindex (meta and X-Robots-Tag), and Google can only drop a URL whose noindex it is allowed to read.
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /portal/',
            'Disallow: /admin',
            'Disallow: /password/',
            'Disallow: /email/',
            'Disallow: /two-factor/',
            'Disallow: /apply-online/start/',
            'Disallow: /webhooks/',
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
        $urls = Sitemap::entries();
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
