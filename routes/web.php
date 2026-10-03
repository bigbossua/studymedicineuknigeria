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
