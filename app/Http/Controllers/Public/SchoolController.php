<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\University;
use App\Support\Funnel;
use App\Support\OgImage;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $q = University::query()->with(['courses' => fn ($c) => $c->medicine()->with('facts'), 'facts'])->orderBy('name');

        $filters = [
            'nation' => $request->string('nation')->toString(),
            'international' => $request->string('international')->toString(), // accepts | international_only | home_only
            'test' => $request->string('test')->toString(),                   // UCAT | GAMSAT | NONE
            'route' => $request->string('route')->toString(),                 // UCAS | DIRECT | BOTH
            'q' => $request->string('q')->toString(),
            'waec' => $request->string('waec')->toString(),                   // published: the university publishes a WAEC/NECO statement we can show here
        ];
        if ($filters['nation']) {
            $q->where('nation', $filters['nation']);
        }
        if ($filters['international']) {
            $q->where('international_policy', $filters['international']);
        }
        if ($filters['q']) {
            $q->where(fn ($w) => $w->where('name', 'like', '%'.$filters['q'].'%')->orWhere('city', 'like', '%'.$filters['q'].'%')->orWhere('medical_school_name', 'like', '%'.$filters['q'].'%'));
        }
        if ($filters['test']) {
            $q->whereHas('courses', fn ($c) => $c->medicine()->where('admissions_test', $filters['test']));
        }
        if ($filters['waec'] === 'published') {
            $visible = (config('site.publish_unverified') || ! app()->isProduction()) ? [ReferenceFact::VERIFIED, ReferenceFact::VERIFY_ON_PAGE, ReferenceFact::REVIEW_DUE, ReferenceFact::SOURCE_CHANGED] : [ReferenceFact::VERIFIED];
            $q->whereHas('facts', fn ($f) => $f->where('key', 'waec_neco_statement')->whereIn('verification_status', $visible));
        }
        if ($filters['route']) {
            $q->whereHas('courses', fn ($c) => $c->medicine()->where('application_route', $filters['route']));
        }

        $universities = $q->get();
        $isFiltered = collect($filters)->filter()->isNotEmpty();

        $seo = Seo::make('UK Medical School Directory for International Applicants',
            'Every UK medical school with its international eligibility, admissions test, application route and published international fee, each with its official source.')
            ->canonical(route('schools.index'))
            ->noindex($isFiltered)
            ->breadcrumbs([['label' => 'Medical Schools']])
            ->reviewed('2026-10-03', '2027');
        if (! $isFiltered) {
            $seo->jsonLd(['@type' => 'ItemList', 'name' => 'UK medical schools', 'numberOfItems' => $universities->count(),
                'itemListElement' => $universities->values()->map(fn ($u, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $u->name, 'url' => route('schools.show', $u)])->all()]);
        }

        return view('schools.index', compact('universities', 'filters', 'seo', 'isFiltered'));
    }

    /** Branded Open Graph card for a university page, rendered once and cached as a static file. */
    public function og(University $university)
    {
        $path = public_path("images/og/schools/{$university->slug}.png");
        if (! is_file($path) || filemtime($path) < $university->updated_at?->getTimestamp()) {
            @mkdir(dirname($path), 0755, true);
            $png = OgImage::render(($university->short_name ?: $university->name).' Medicine: international entry', 'UK medical school directory · what the university publishes');
            @file_put_contents($path, $png);
        } else {
            $png = file_get_contents($path);
        }

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function show(University $university): View
    {
        $university->load(['courses' => fn ($c) => $c->medicine()->with('facts'), 'facts']);
        $course = $university->primaryCourse();
        $seo = Seo::make(($university->short_name ?: $university->name).' Medicine: international entry',
            "What {$university->name} publishes for international and Nigerian applicants to Medicine: eligibility, entry requirements, admissions test, fees, application route, with official sources and verification dates.")
            ->canonical(route('schools.show', $university))
            ->image(route('schools.og', $university))
            ->article()
            ->breadcrumbs([['label' => 'Medical Schools', 'url' => route('schools.index')], ['label' => $university->name]])
            ->reviewed('2026-10-03', '2027')
            ->jsonLd(['@type' => 'CollegeOrUniversity', 'name' => $university->name, 'url' => $university->website_url ?? $course?->official_url, 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $university->city, 'addressCountry' => 'GB']]);

        if ($course) {
            $courseLd = ['@type' => 'Course', 'name' => $course->title, 'provider' => ['@type' => 'CollegeOrUniversity', 'name' => $university->name], 'url' => $course->official_url ?? $university->website_url];
            if ($course->shortUcasCode()) {
                $courseLd['courseCode'] = $course->shortUcasCode();
            }
            // An Offer is emitted only for a fee the university publishes and we have verified on its page (architecture 18.3).
            $fee = $course->internationalFee();
            if ($fee && $fee->verification_status === ReferenceFact::VERIFIED && $fee->value_number) {
                $courseLd['offers'] = ['@type' => 'Offer', 'category' => 'International tuition fee per year', 'price' => (string) $fee->value_number, 'priceCurrency' => 'GBP'];
            }
            $seo->jsonLd($courseLd);
        }

        // Public university pages stay noindex until the record is marked published by staff
        if (! $university->published) {
            $seo->noindex();
        }

        Funnel::track('course_viewed', ['school' => $university->slug]);

        return view('schools.show', ['university' => $university, 'course' => $course, 'seo' => $seo, 'ucas' => Topic::bySlug('ucas-2027'), 'ucat' => Topic::bySlug('ucat-2026')]);
    }
}
