<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
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
        $q = University::query()->medicalSchools()->with(['courses' => fn ($c) => $c->medicine()->with('facts'), 'facts'])->orderBy('name');

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
            $visible = ReferenceFact::showsUnverified() ? [ReferenceFact::VERIFIED, ReferenceFact::VERIFY_ON_PAGE, ReferenceFact::REVIEW_DUE, ReferenceFact::SOURCE_CHANGED] : [ReferenceFact::VERIFIED];
            $q->whereHas('facts', fn ($f) => $f->where('key', 'waec_neco_statement')->whereIn('verification_status', $visible));
        }
        if ($filters['route']) {
            $q->whereHas('courses', fn ($c) => $c->medicine()->where('application_route', $filters['route']));
        }

        $universities = $q->get();
        $isFiltered = collect($filters)->filter()->isNotEmpty();

        $seo = Seo::make('UK medical schools that accept international students',
            'Every UK medical school with its international eligibility, admissions test, application route and published international fee, each with its official source.')
            ->canonical(route('schools.index'))
            ->noindex($isFiltered)
            ->breadcrumbs([['label' => 'Medical Schools']])
            ->reviewed('2026-10-03', '2027');
        if (! $isFiltered) {
            $seo->jsonLd(['@type' => 'ItemList', 'name' => 'UK medical schools', 'numberOfItems' => $universities->count(),
                'itemListElement' => $universities->values()->map(fn ($u, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $u->name, 'url' => route('schools.show', $u)])->all()]);
        }

        // the map and nation counts always show the whole directory, whatever the filters
        $all = University::query()->medicalSchools()->get(['id', 'slug', 'name', 'city', 'nation', 'international_policy']);

        return view('schools.index', compact('universities', 'filters', 'seo', 'isFiltered', 'all'));
    }

    /** Branded Open Graph card for a university page, rendered once and cached as a static file. */
    public function og(University $university)
    {
        abort_unless($university->isMedicalSchool(), 404);
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

    /** The latest official-page verification among the facts this page shows (its "last reviewed" date). */
    private function lastVerified(University $university, ?Course $course): ?string
    {
        $dates = $university->facts->merge($course?->facts ?? [])->where('verification_status', ReferenceFact::VERIFIED)->pluck('verified_at')->filter();

        return $dates->isEmpty() ? null : $dates->max()->toDateString();
    }

    public function show(University $university): View
    {
        abort_unless($university->isMedicalSchool(), 404); // a provider recorded only for another subject has no page here
        $university->load(['courses' => fn ($c) => $c->medicine()->with('facts'), 'facts']);
        $course = $university->primaryCourse();
        $seo = Seo::make(($university->short_name ?: $university->name).' Medicine: international entry',
            "{$university->name} Medicine for Nigerian and international applicants: entry requirements, English, fees and how to apply, from official pages.")
            ->canonical(route('schools.show', $university))
            ->image(route('schools.og', $university))
            ->article()
            ->breadcrumbs([['label' => 'Medical Schools', 'url' => route('schools.index')], ['label' => $university->name]])
            ->reviewed($this->lastVerified($university, $course) ?? '2026-10-03', '2027')
            ->jsonLd(['@type' => 'CollegeOrUniversity', 'name' => $university->name, 'url' => $university->website_url ?? $course?->official_url, 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $university->city, 'addressCountry' => 'GB']]);

        if ($course) {
            $courseLd = ['@type' => 'Course', 'name' => $course->title, 'provider' => ['@type' => 'CollegeOrUniversity', 'name' => $university->name], 'url' => $course->official_url ?? $university->website_url];
            if ($course->shortUcasCode()) {
                $courseLd['courseCode'] = $course->shortUcasCode();
            }
            // No Offer: we do not sell the course, so the university's fee stays in the visible text only (architecture 18.3)
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
