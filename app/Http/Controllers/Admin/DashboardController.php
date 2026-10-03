<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\ReferenceFact;
use App\Support\Seo;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'seo' => Seo::make('Admin')->noindex(),
            'counts' => [
                'leads_new' => Lead::where('status', 'new')->count(),
                'applications_active' => Application::whereNull('withdrawn_at')->whereNull('closed_at')->count(),
                'docs_review' => Document::whereIn('status', [DocumentStatus::UPLOADED, DocumentStatus::UNDER_REVIEW])->count(),
                'docs_complete_awaiting' => Application::where('stage', 'DOCUMENTS_COMPLETE')->count(),
                'approvals_pending' => Application::where('stage', 'READY_FOR_STUDENT_APPROVAL')->count(),
                'approved_to_submit' => Application::whereIn('stage', ['STUDENT_APPROVED', 'READY_FOR_UNIVERSITY_SUBMISSION'])->count(),
                'payments_manual' => Payment::where('status', 'MANUAL_REVIEW')->count(),
                'facts_pending' => ReferenceFact::where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->count(),
                'facts_review_due' => ReferenceFact::where('verification_status', ReferenceFact::REVIEW_DUE)->count(),
            ],
            'recent' => Application::with('user', 'tier')->latest('last_activity_at')->take(10)->get(),
            'byStage' => Application::selectRaw('stage, count(*) c')->groupBy('stage')->pluck('c', 'stage'),
        ]);
    }
}
