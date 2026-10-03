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
Route::get('study-medicine-in-the-uk', [\App\Http\Controllers\Public\ContentController::class, 'medicine'])->name('medicine.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('study-medicine-in-the-uk/from-nigeria', [\App\Http\Controllers\Public\ContentController::class, 'nigeria'])->name('medicine.nigeria')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('study-medicine-in-the-uk/foundation-routes', [\App\Http\Controllers\Public\ContentController::class, 'foundation'])->name('medicine.foundation')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// Medical schools directory
Route::get('medical-schools', [\App\Http\Controllers\Public\SchoolController::class, 'index'])->name('schools.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('medical-schools/{university:slug}', [\App\Http\Controllers\Public\SchoolController::class, 'show'])->name('schools.show');

// Requirements hub
Route::get('requirements', [\App\Http\Controllers\Public\ContentController::class, 'requirements'])->name('requirements.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/waec', [\App\Http\Controllers\Public\ContentController::class, 'waec'])->name('requirements.waec')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/neco', [\App\Http\Controllers\Public\ContentController::class, 'neco'])->name('requirements.neco')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/a-levels', [\App\Http\Controllers\Public\ContentController::class, 'alevels'])->name('requirements.alevels')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/nigerian-degree-graduate-entry', [\App\Http\Controllers\Public\ContentController::class, 'gem'])->name('requirements.gem')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/english-language', [\App\Http\Controllers\Public\ContentController::class, 'english'])->name('requirements.english')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// Fees
Route::get('fees', [\App\Http\Controllers\Public\ContentController::class, 'fees'])->name('fees.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('fees/cost-of-studying-medicine-in-the-uk', [\App\Http\Controllers\Public\ContentController::class, 'totalCost'])->name('fees.total');

// Admissions
Route::get('admissions', [\App\Http\Controllers\Public\ContentController::class, 'admissions'])->name('admissions.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('admissions/ucat', [\App\Http\Controllers\Public\ContentController::class, 'ucat'])->name('admissions.ucat')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('admissions/ucas-deadlines-2027', [\App\Http\Controllers\Public\ContentController::class, 'ucas2027'])->name('admissions.ucas2027')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('admissions/how-to-apply', [\App\Http\Controllers\Public\ContentController::class, 'howToApply'])->name('admissions.howto')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// FAQ
Route::get('faq', [\App\Http\Controllers\Public\ContentController::class, 'faq'])->name('faq.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// Apply Online (commercial gateway)
Route::get('apply-online', [\App\Http\Controllers\Public\ContentController::class, 'apply'])->name('apply.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('apply-online/services', [\App\Http\Controllers\Public\ContentController::class, 'services'])->name('apply.services')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('apply-online/eligibility', [\App\Http\Controllers\Public\ContentController::class, 'eligibility'])->name('apply.eligibility')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::post('apply-online/eligibility', [\App\Http\Controllers\Public\ContentController::class, 'eligibilitySubmit'])->middleware('throttle:10,10')->name('apply.eligibility.submit');

// Organisation & legal
Route::get('about', [\App\Http\Controllers\Public\ContentController::class, 'about'])->name('about')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('our-status', [\App\Http\Controllers\Public\ContentController::class, 'status'])->name('status')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('contact', [\App\Http\Controllers\Public\ContentController::class, 'contact'])->name('contact')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('privacy', fn () => app(\App\Http\Controllers\Public\ContentController::class)->legal('privacy'))->name('legal.privacy')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('terms', fn () => app(\App\Http\Controllers\Public\ContentController::class)->legal('terms'))->name('legal.terms')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('application-terms', fn () => app(\App\Http\Controllers\Public\ContentController::class)->legal('application-terms'))->name('legal.application-terms')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('refund-policy', fn () => app(\App\Http\Controllers\Public\ContentController::class)->legal('refunds'))->name('legal.refunds')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);

// Auth placeholder until the portal is implemented
Route::get('login', [\App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->middleware('guest')->name('login');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Auth\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('password/forgot', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('password/email', [AuthController::class, 'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('password/reset/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('password/reset', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) { $request->fulfill(); return redirect()->route('portal.dashboard')->with('status', 'Your email address is verified.'); })->middleware('signed')->name('verification.verify');
    Route::post('email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Student portal (private, noindex)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Portal\DashboardController::class, 'index'])->name('dashboard');
    Route::post('start', [\App\Http\Controllers\Portal\DashboardController::class, 'start'])->name('start');
    Route::get('profile', [\App\Http\Controllers\Portal\ProfileController::class, 'show'])->name('profile');
    Route::put('profile', [\App\Http\Controllers\Portal\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [\App\Http\Controllers\Portal\ProfileController::class, 'password'])->name('profile.password');
    Route::get('profile/export', [\App\Http\Controllers\Portal\ProfileController::class, 'export'])->name('profile.export');
    Route::post('profile/delete', [\App\Http\Controllers\Portal\ProfileController::class, 'requestDeletion'])->name('profile.delete');

    Route::prefix('{application}')->group(function () {
        Route::get('application', [\App\Http\Controllers\Portal\ApplicationController::class, 'index'])->name('application.index');
        Route::get('application/{step}', [\App\Http\Controllers\Portal\ApplicationController::class, 'step'])->name('application.step');
        Route::post('application/{step}', [\App\Http\Controllers\Portal\ApplicationController::class, 'save'])->name('application.save');
        Route::post('withdraw', [\App\Http\Controllers\Portal\ApplicationController::class, 'withdraw'])->name('application.withdraw');
        Route::get('documents', [\App\Http\Controllers\Portal\DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [\App\Http\Controllers\Portal\DocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}', [\App\Http\Controllers\Portal\DocumentController::class, 'upload'])->middleware('throttle:20,10')->name('documents.upload');
        Route::get('documents/{document}/v/{version}', [\App\Http\Controllers\Portal\DocumentController::class, 'download'])->name('documents.download');
        Route::get('payments', [\App\Http\Controllers\Portal\PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/checkout', [\App\Http\Controllers\Portal\PaymentController::class, 'checkout'])->name('payments.checkout');
        Route::get('payments/return', [\App\Http\Controllers\Portal\PaymentController::class, 'return'])->name('payments.return');
        Route::post('payments/manual', [\App\Http\Controllers\Portal\PaymentController::class, 'manualTransfer'])->name('payments.manual');
        Route::get('approve', [\App\Http\Controllers\Portal\ApprovalController::class, 'show'])->name('approve.show');
        Route::post('approve', [\App\Http\Controllers\Portal\ApprovalController::class, 'approve'])->name('approve.store');
        Route::post('approve/changes', [\App\Http\Controllers\Portal\ApprovalController::class, 'requestChanges'])->name('approve.changes');
        Route::get('submissions', [\App\Http\Controllers\Portal\SubmissionController::class, 'index'])->name('submissions.index');
        Route::post('submissions/{submission}/recorded', [\App\Http\Controllers\Portal\SubmissionController::class, 'recordSubmitted'])->name('submissions.recorded');
        Route::get('messages', [\App\Http\Controllers\Portal\MessageController::class, 'index'])->name('messages.index');
        Route::post('messages', [\App\Http\Controllers\Portal\MessageController::class, 'store'])->middleware('throttle:30,10')->name('messages.store');
    });
});

Route::post('webhooks/stripe', \App\Http\Controllers\Webhooks\StripeWebhookController::class)->name('webhooks.stripe');

/*
|--------------------------------------------------------------------------
| Admin (staff + admin roles)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('applications', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'show'])->name('applications.show');
    Route::post('applications/{application}/assign', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'assign'])->name('applications.assign');
    Route::post('applications/{application}/stage', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'stage'])->name('applications.stage');
    Route::post('applications/{application}/documents/request', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'requestDocument'])->name('applications.document.request');
    Route::post('applications/{application}/documents/{document}/review', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'reviewDocument'])->name('applications.document.review');
    Route::get('applications/{application}/documents/{document}/view', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'document'])->name('applications.document');
    Route::post('applications/{application}/submissions', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'proposeSubmission'])->name('applications.submission.propose');
    Route::post('applications/{application}/submissions/{submission}', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'updateSubmission'])->name('applications.submission.update');
    Route::post('applications/{application}/payments/{payment}/confirm', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'confirmPayment'])->name('applications.payment.confirm');
    Route::post('applications/{application}/reply', [\App\Http\Controllers\Admin\ApplicationAdminController::class, 'reply'])->name('applications.reply');
    Route::get('leads', [\App\Http\Controllers\Admin\MiscAdminController::class, 'leads'])->name('leads');
    Route::post('leads/{lead}/status', [\App\Http\Controllers\Admin\MiscAdminController::class, 'leadStatus'])->name('leads.status');
    Route::get('payments', [\App\Http\Controllers\Admin\MiscAdminController::class, 'payments'])->name('payments');
    Route::get('services', [\App\Http\Controllers\Admin\MiscAdminController::class, 'tiers'])->name('tiers');
    Route::post('services/prices/{price}', [\App\Http\Controllers\Admin\MiscAdminController::class, 'priceUpdate'])->name('tiers.price');
    Route::get('verification', [\App\Http\Controllers\Admin\ReferenceAdminController::class, 'index'])->name('reference.index');
    Route::post('verification/{fact}', [\App\Http\Controllers\Admin\ReferenceAdminController::class, 'update'])->name('reference.update');
    Route::get('universities', [\App\Http\Controllers\Admin\ReferenceAdminController::class, 'universities'])->name('reference.universities');
    Route::post('universities/{university}/publish', [\App\Http\Controllers\Admin\ReferenceAdminController::class, 'publishUniversity'])->name('reference.university.publish');
    Route::get('redirects', [\App\Http\Controllers\Admin\MiscAdminController::class, 'redirects'])->name('redirects');
    Route::post('redirects', [\App\Http\Controllers\Admin\MiscAdminController::class, 'redirectStore'])->name('redirects.store');
    Route::delete('redirects/{id}', [\App\Http\Controllers\Admin\MiscAdminController::class, 'redirectDelete'])->name('redirects.delete');
    Route::get('users', [\App\Http\Controllers\Admin\MiscAdminController::class, 'users'])->name('users');
    Route::post('users/{user}/role', [\App\Http\Controllers\Admin\MiscAdminController::class, 'userRole'])->name('users.role');
    Route::get('audit', [\App\Http\Controllers\Admin\MiscAdminController::class, 'audit'])->name('audit');
});
