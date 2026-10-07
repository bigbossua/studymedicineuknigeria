<?php

namespace App\Support;

use App\Models\ReferenceFact;
use App\Models\University;
use Illuminate\Support\Facades\Route;

/** The URLs /sitemap.xml lists: published, indexable routes (each carries a 'sitemap' default) and published school pages. */
class Sitemap
{
    /** @return list<array{loc: string, lastmod: ?string, changefreq: string}> */
    public static function entries(): array
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
        // University pages are listed once staff (or the publication list) have published them: the same rule that makes them indexable
        foreach (University::medicalSchools()->where('published', true)->orderBy('name')->get() as $u) {
            $verified = $u->facts()->where('verification_status', ReferenceFact::VERIFIED)->max('verified_at');
            $urls[] = ['loc' => route('schools.show', $u), 'lastmod' => $verified ? substr((string) $verified, 0, 10) : null, 'changefreq' => 'monthly'];
        }

        return $urls;
    }

    /** @return list<string> */
    public static function urls(): array
    {
        return array_column(self::entries(), 'loc');
    }
}
