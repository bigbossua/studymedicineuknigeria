<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

/**
 * Serves the public information architecture. Pages that are not yet written render a
 * noindex "in preparation" view so no thin content is ever indexed (page asset register rule).
 */
class PageController extends Controller
{
    public function home(): View
    {
        // Brand-first: the core query ("study medicine in the UK from Nigeria") belongs to the core landing page
        // (register C01); home is the entry point and must not compete with it.
        $seo = Seo::make('Study Medicine UK Nigeria: independent UK Medicine applications',
            'Check published entry requirements for WAEC, NECO, A-levels and Nigerian degrees, compare verified fees and UK medical schools, and apply online with support.')
            ->canonical(route('home'))
            ->jsonLd([
                '@type' => 'WebSite',
                'name' => config('site.name'),
                'url' => url('/'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('schools.index').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ]);

        return view('pages.home', ['seo' => $seo]);
    }

    /** Placeholder for pages on the register that are not yet produced. Always noindex. */
    public function pending(string $title, array $crumbs = []): View
    {
        $seo = Seo::make($title)->noindex()->breadcrumbs($crumbs);

        return view('pages.pending', ['seo' => $seo, 'title' => $title]);
    }
}
