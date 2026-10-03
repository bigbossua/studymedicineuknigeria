<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $q = University::query()->with(['courses.facts', 'facts'])->orderBy('name');

        $filters = [
            'nation' => $request->string('nation')->toString(),
            'international' => $request->string('international')->toString(), // accepts | international_only | home_only
            'test' => $request->string('test')->toString(),                   // UCAT | GAMSAT | NONE
            'route' => $request->string('route')->toString(),                 // UCAS | DIRECT | BOTH
            'q' => $request->string('q')->toString(),
        ];
        if ($filters['nation']) $q->where('nation', $filters['nation']);
        if ($filters['international']) $q->where('international_policy', $filters['international']);
        if ($filters['q']) $q->where(fn ($w) => $w->where('name', 'like', '%'.$filters['q'].'%')->orWhere('city', 'like', '%'.$filters['q'].'%')->orWhere('medical_school_name', 'like', '%'.$filters['q'].'%'));
        if ($filters['test']) $q->whereHas('courses', fn ($c) => $c->where('admissions_test', $filters['test']));
        if ($filters['route']) $q->whereHas('courses', fn ($c) => $c->where('application_route', $filters['route']));

        $universities = $q->get();
        $isFiltered = collect($filters)->filter()->isNotEmpty();

        $seo = Seo::make('UK Medical School Directory for International Applicants',
            'Every UK medical school with its international eligibility, admissions test, application route and published international fee, each with an official source and a last-verified date.')
            ->canonical(route('schools.index'))
            ->noindex($isFiltered)
            ->breadcrumbs([['label' => 'Medical Schools']])
            ->reviewed('2026-10-03', '2027');

        return view('schools.index', compact('universities', 'filters', 'seo', 'isFiltered'));
    }

    public function show(University $university): View
    {
        $university->load(['courses.facts', 'facts']);
        $course = $university->primaryCourse();
        $seo = Seo::make($university->name.' — Medicine for international applicants',
            "What {$university->name} publishes for international and Nigerian applicants to Medicine: eligibility, entry requirements, admissions test, fees, application route, with official sources and verification dates.")
            ->canonical(route('schools.show', $university))
            ->article()
            ->breadcrumbs([['label' => 'Medical Schools', 'url' => route('schools.index')], ['label' => $university->name]])
            ->reviewed('2026-10-03', '2027')
            ->jsonLd(['@type' => 'CollegeOrUniversity', 'name' => $university->name, 'url' => $university->website_url ?? $course?->official_url, 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $university->city, 'addressCountry' => 'GB']]);

        // Public university pages stay noindex until the record is marked published by staff
        if (! $university->published) $seo->noindex();

        return view('schools.show', compact('university', 'course', 'seo'));
    }
}
