<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Submission;
use App\Services\Applications\StageResolver;
use App\Support\Seo;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);

        return view('portal.submissions', ['seo' => Seo::make('University submissions')->noindex(), 'application' => $application, 'submissions' => $application->submissions()->with('university', 'course', 'events', 'authorisation', 'choices.course.university')->get()]);
    }

    /** For student-performed routes: the student records the official reference and date. */
    public function recordSubmitted(Request $request, Application $application, Submission $submission)
    {
        abort_unless($application->user_id === auth()->id() && $submission->application_id === $application->id, 403);
        abort_unless(in_array($submission->route_code, ['UCAS_STUDENT', 'DIRECT_PORTAL_STUDENT'], true) && in_array($submission->status, ['AUTHORISED', 'PACKAGE_READY'], true), 403);
        $data = $request->validate(['external_reference' => 'required|string|max:64', 'submitted_on' => 'required|date|before_or_equal:today']);
        $submission->transition('SUBMITTED', null, 'Recorded by the student', $data['external_reference']);
        $submission->forceFill(['submitted_at' => $data['submitted_on'], 'submitted_by' => 'STUDENT'])->save();
        $application->record('submission.sent', ['submission_id' => $submission->id, 'by' => 'student'], $request->user()->id);
        app(StageResolver::class)->sync($application);

        return back()->with('status', 'Recorded. We will track the university\'s response with you.');
    }
}
