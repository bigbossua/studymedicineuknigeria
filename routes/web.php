<?php

use App\Http\Controllers\Admin\ApplicationAdminController;
use App\Http\Controllers\Admin\MiscAdminController;
use App\Http\Controllers\Admin\ReferenceAdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Portal\ApplicationController;
use App\Http\Controllers\Portal\ApprovalController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\MessageController;
use App\Http\Controllers\Portal\PaymentController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\SubmissionController;
use App\Http\Controllers\Public\ContentController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SchoolController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
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
Route::get('study-medicine-in-the-uk', [ContentController::class, 'medicine'])->name('medicine.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('study-medicine-in-the-uk/from-nigeria', [ContentController::class, 'nigeria'])->name('medicine.nigeria')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('study-medicine-in-the-uk/foundation-routes', [ContentController::class, 'foundation'])->name('medicine.foundation')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// Medical schools directory
Route::get('medical-schools', [SchoolController::class, 'index'])->name('schools.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('medical-schools/{university:slug}', [SchoolController::class, 'show'])->name('schools.show');

// Requirements hub
Route::get('requirements', [ContentController::class, 'requirements'])->name('requirements.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/waec', [ContentController::class, 'waec'])->name('requirements.waec')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/neco', [ContentController::class, 'neco'])->name('requirements.neco')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/a-levels', [ContentController::class, 'alevels'])->name('requirements.alevels')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/nigerian-degree-graduate-entry', [ContentController::class, 'gem'])->name('requirements.gem')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('requirements/english-language', [ContentController::class, 'english'])->name('requirements.english')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// Fees
Route::get('fees', [ContentController::class, 'fees'])->name('fees.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('fees/cost-of-studying-medicine-in-the-uk', [ContentController::class, 'totalCost'])->name('fees.total')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly', 'gate' => 'topics-verified:student-visa,costs-2026,ucat-2026,ucas-2027']);
Route::get('working-in-the-uk', [ContentController::class, 'working'])->name('working.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly', 'gate' => 'topics-verified:student-visa,graduate-visa,gmc-registration']);

// Admissions
Route::get('admissions', [ContentController::class, 'admissions'])->name('admissions.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('admissions/ucat', [ContentController::class, 'ucat'])->name('admissions.ucat')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('admissions/ucas-deadlines-2027', [ContentController::class, 'ucas2027'])->name('admissions.ucas2027')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'weekly']);
Route::get('admissions/how-to-apply', [ContentController::class, 'howToApply'])->name('admissions.howto')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// FAQ
Route::get('faq', [ContentController::class, 'faq'])->name('faq.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);

// Apply Online (commercial gateway)
Route::get('apply-online', [ContentController::class, 'apply'])->name('apply.index')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('apply-online/services', [ContentController::class, 'services'])->name('apply.services')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('apply-online/eligibility', [ContentController::class, 'eligibility'])->name('apply.eligibility')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::post('apply-online/eligibility', [ContentController::class, 'eligibilitySubmit'])->middleware('throttle:10,10')->name('apply.eligibility.submit');

// Organisation & legal
Route::get('about', [ContentController::class, 'about'])->name('about')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('our-status', [ContentController::class, 'status'])->name('status')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'monthly']);
Route::get('contact', [ContentController::class, 'contact'])->name('contact')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('privacy', fn () => app(ContentController::class)->legal('privacy'))->name('legal.privacy')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('terms', fn () => app(ContentController::class)->legal('terms'))->name('legal.terms')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('application-terms', fn () => app(ContentController::class)->legal('application-terms'))->name('legal.application-terms')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);
Route::get('refund-policy', fn () => app(ContentController::class)->legal('refunds'))->name('legal.refunds')->defaults('sitemap', ['lastmod' => '2026-10-03', 'changefreq' => 'yearly']);

// Auth placeholder until the portal is implemented
Route::get('login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

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
    Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('portal.dashboard')->with('status', 'Your email address is verified.');
    })->middleware('signed')->name('verification.verify');
    Route::post('email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1')->name('verification.send');

    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('setup', [TwoFactorController::class, 'confirm'])->middleware('throttle:10,1')->name('confirm');
        Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('recovery-codes');
        Route::get('challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('challenge', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('verify');
        Route::post('disable', [TwoFactorController::class, 'disable'])->name('disable');
    });
});

/*
|--------------------------------------------------------------------------
| Student portal (private, noindex)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', '2fa'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('start', [DashboardController::class, 'start'])->name('start');
    Route::get('profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('profile/export', [ProfileController::class, 'export'])->name('profile.export');
    Route::post('profile/delete', [ProfileController::class, 'requestDeletion'])->name('profile.delete');

    Route::prefix('{application}')->group(function () {
        Route::get('application', [ApplicationController::class, 'index'])->name('application.index');
        Route::get('application/{step}', [ApplicationController::class, 'step'])->name('application.step');
        Route::post('application/{step}', [ApplicationController::class, 'save'])->name('application.save');
        Route::post('withdraw', [ApplicationController::class, 'withdraw'])->name('application.withdraw');
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}', [DocumentController::class, 'upload'])->middleware('throttle:20,10')->name('documents.upload');
        Route::get('documents/{document}/v/{version}', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
        Route::get('payments/return', [PaymentController::class, 'return'])->name('payments.return');
        Route::post('payments/manual', [PaymentController::class, 'manualTransfer'])->name('payments.manual');
        Route::get('approve', [ApprovalController::class, 'show'])->name('approve.show');
        Route::post('approve', [ApprovalController::class, 'approve'])->name('approve.store');
        Route::post('approve/changes', [ApprovalController::class, 'requestChanges'])->name('approve.changes');
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::post('submissions/{submission}/recorded', [SubmissionController::class, 'recordSubmitted'])->name('submissions.recorded');
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('messages', [MessageController::class, 'store'])->middleware('throttle:30,10')->name('messages.store');
    });
});

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

/*
|--------------------------------------------------------------------------
| Admin (staff + admin roles)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'staff', '2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('applications', [ApplicationAdminController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}', [ApplicationAdminController::class, 'show'])->name('applications.show');
    Route::post('applications/{application}/assign', [ApplicationAdminController::class, 'assign'])->name('applications.assign');
    Route::post('applications/{application}/stage', [ApplicationAdminController::class, 'stage'])->name('applications.stage');
    Route::post('applications/{application}/documents/request', [ApplicationAdminController::class, 'requestDocument'])->name('applications.document.request');
    Route::post('applications/{application}/documents/{document}/review', [ApplicationAdminController::class, 'reviewDocument'])->name('applications.document.review');
    Route::get('applications/{application}/documents/{document}/view', [ApplicationAdminController::class, 'document'])->name('applications.document');
    Route::post('applications/{application}/submissions', [ApplicationAdminController::class, 'proposeSubmission'])->name('applications.submission.propose');
    Route::post('applications/{application}/submissions/{submission}', [ApplicationAdminController::class, 'updateSubmission'])->name('applications.submission.update');
    Route::post('applications/{application}/payments/{payment}/confirm', [ApplicationAdminController::class, 'confirmPayment'])->name('applications.payment.confirm');
    Route::post('applications/{application}/reply', [ApplicationAdminController::class, 'reply'])->name('applications.reply');
    Route::get('leads', [MiscAdminController::class, 'leads'])->name('leads');
    Route::post('leads/{lead}/status', [MiscAdminController::class, 'leadStatus'])->name('leads.status');
    Route::get('payments', [MiscAdminController::class, 'payments'])->name('payments');
    Route::get('services', [MiscAdminController::class, 'tiers'])->name('tiers');
    Route::post('services/prices/{price}', [MiscAdminController::class, 'priceUpdate'])->name('tiers.price');
    Route::get('verification', [ReferenceAdminController::class, 'index'])->name('reference.index');
    Route::post('verification/{fact}', [ReferenceAdminController::class, 'update'])->name('reference.update');
    Route::get('universities', [ReferenceAdminController::class, 'universities'])->name('reference.universities');
    Route::post('universities/{university}/publish', [ReferenceAdminController::class, 'publishUniversity'])->name('reference.university.publish');
    Route::get('redirects', [MiscAdminController::class, 'redirects'])->name('redirects');
    Route::post('redirects', [MiscAdminController::class, 'redirectStore'])->name('redirects.store');
    Route::delete('redirects/{id}', [MiscAdminController::class, 'redirectDelete'])->name('redirects.delete');
    Route::get('users', [MiscAdminController::class, 'users'])->name('users');
    Route::post('users/{user}/role', [MiscAdminController::class, 'userRole'])->name('users.role');
    Route::post('users/{user}/two-factor/reset', [MiscAdminController::class, 'userTwoFactorReset'])->name('users.two-factor.reset');
    Route::get('audit', [MiscAdminController::class, 'audit'])->name('audit');
    Route::get('funnel', [MiscAdminController::class, 'funnel'])->name('funnel');
});
