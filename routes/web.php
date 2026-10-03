<?php

use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public information architecture — docs/decision/information-architecture.md
|--------------------------------------------------------------------------
| Routes carry a 'sitemap' default only when the page is published and indexable.
| Pages still "pending" render a noindex placeholder and are NOT in the sitemap.
*/

Route::get('/', [PageController::class, 'home'])->name('home')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);

Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Helper to register a pending page
$pending = function (string $uri, string $name, string $title, array $crumbs = []) {
    Route::get($uri, fn () => app(PageController::class)->pending($title, $crumbs))->name($name);
};

// Medicine pillar
$pending('study-medicine-in-the-uk', 'medicine.index', 'Study Medicine in the UK', [['label' => 'Medicine']]);
$pending('study-medicine-in-the-uk/from-nigeria', 'medicine.nigeria', 'Study Medicine in the UK from Nigeria', [['label' => 'Medicine', 'url' => '/study-medicine-in-the-uk/'], ['label' => 'From Nigeria']]);
$pending('study-medicine-in-the-uk/foundation-routes', 'medicine.foundation', 'Foundation and gateway routes to Medicine', [['label' => 'Medicine', 'url' => '/study-medicine-in-the-uk/'], ['label' => 'Foundation routes']]);

// Medical schools directory
Route::get('medical-schools', [\App\Http\Controllers\Public\SchoolController::class, 'index'])->name('schools.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('medical-schools/{university:slug}', [\App\Http\Controllers\Public\SchoolController::class, 'show'])->name('schools.show');

// Requirements hub
$pending('requirements', 'requirements.index', 'What do I need to study Medicine in the UK?', [['label' => 'Requirements']]);
$pending('requirements/waec', 'requirements.waec', 'WAEC (WASSCE) and UK Medicine', [['label' => 'Requirements', 'url' => '/requirements/'], ['label' => 'WAEC']]);
$pending('requirements/neco', 'requirements.neco', 'NECO and UK Medicine', [['label' => 'Requirements', 'url' => '/requirements/'], ['label' => 'NECO']]);
$pending('requirements/a-levels', 'requirements.alevels', 'A-levels for UK Medicine', [['label' => 'Requirements', 'url' => '/requirements/'], ['label' => 'A-levels']]);
$pending('requirements/nigerian-degree-graduate-entry', 'requirements.gem', 'Graduate Entry Medicine with a Nigerian degree', [['label' => 'Requirements', 'url' => '/requirements/'], ['label' => 'Nigerian degree']]);
$pending('requirements/english-language', 'requirements.english', 'English language requirements for Medicine', [['label' => 'Requirements', 'url' => '/requirements/'], ['label' => 'English language']]);

// Fees
$pending('fees', 'fees.index', 'UK medical school fees for international students', [['label' => 'Fees']]);
$pending('fees/cost-of-studying-medicine-in-the-uk', 'fees.total', 'Total cost of studying Medicine in the UK', [['label' => 'Fees', 'url' => '/fees/'], ['label' => 'Total cost']]);

// Admissions
$pending('admissions', 'admissions.index', 'Admissions: UCAS, UCAT and interviews', [['label' => 'Admissions']]);
$pending('admissions/ucat', 'admissions.ucat', 'UCAT for Nigerian students', [['label' => 'Admissions', 'url' => '/admissions/'], ['label' => 'UCAT']]);
$pending('admissions/ucas-deadlines-2027', 'admissions.ucas2027', 'UCAS deadlines and timeline for 2027 entry', [['label' => 'Admissions', 'url' => '/admissions/'], ['label' => 'UCAS 2027']]);
$pending('admissions/how-to-apply', 'admissions.howto', 'How to apply: UCAS and direct-application medical schools', [['label' => 'Admissions', 'url' => '/admissions/'], ['label' => 'How to apply']]);

// FAQ
$pending('faq', 'faq.index', 'Questions Nigerian applicants ask', [['label' => 'FAQ']]);

// Apply Online (commercial gateway)
$pending('apply-online', 'apply.index', 'Apply Online', [['label' => 'Apply Online']]);
$pending('apply-online/services', 'apply.services', 'Services and pricing', [['label' => 'Apply Online', 'url' => '/apply-online/'], ['label' => 'Services']]);
$pending('apply-online/eligibility', 'apply.eligibility', 'Check your eligibility', [['label' => 'Apply Online', 'url' => '/apply-online/'], ['label' => 'Eligibility']]);

// Organisation & legal
$pending('about', 'about', 'About', [['label' => 'About']]);
$pending('our-status', 'status', 'Our status', [['label' => 'Our status']]);
$pending('contact', 'contact', 'Contact', [['label' => 'Contact']]);
$pending('privacy', 'legal.privacy', 'Privacy notice', [['label' => 'Privacy']]);
$pending('terms', 'legal.terms', 'Terms of use', [['label' => 'Terms']]);
$pending('application-terms', 'legal.application-terms', 'Application service terms', [['label' => 'Application terms']]);
$pending('refund-policy', 'legal.refunds', 'Refund policy', [['label' => 'Refund policy']]);

// Auth placeholder until the portal is implemented
$pending('login', 'login', 'Student portal', [['label' => 'Student portal']]);
