<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Applications\ChecklistBuilder;
use App\Services\Applications\FormSteps;
use App\Services\Applications\StageResolver;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function __construct(private StageResolver $stages, private ChecklistBuilder $checklist) {}

    public function index(Application $application)
    {
        $this->authorizeOwner($application);

        return view('portal.application.index', ['seo' => Seo::make('Your application')->noindex(), 'application' => $application, 'steps' => FormSteps::all()]);
    }

    public function step(Application $application, string $step)
    {
        $this->authorizeOwner($application);
        abort_unless(array_key_exists($step, FormSteps::all()), 404);
        $steps = FormSteps::all();
        $keys = array_keys($steps);
        $i = array_search($step, $keys, true);

        return view('portal.application.step', [
            'seo' => Seo::make($steps[$step]['title'].' — application')->noindex(), 'application' => $application, 'step' => $step, 'meta' => $steps[$step],
            'index' => $i + 1, 'total' => count($keys), 'prev' => $keys[$i - 1] ?? null, 'next' => $keys[$i + 1] ?? null,
            'data' => $application->formSection($step), 'locked' => $this->isLocked($application),
        ]);
    }

    /** Autosave (XHR) and full submit share this endpoint. */
    public function save(Request $request, Application $application, string $step)
    {
        $this->authorizeOwner($application);
        abort_unless(array_key_exists($step, FormSteps::all()), 404);
        if ($this->isLocked($application)) {
            return $request->expectsJson() ? response()->json(['locked' => true], 423) : back()->with('error', 'This section is locked while your approved package is being submitted. Ask us to reopen it if something must change.');
        }
        $input = $request->except(['_token', '_method', 'intent']);
        $form = $application->form ?? [];
        $form[$step] = $input;
        $validator = Validator::make($input, FormSteps::rules($step));
        $complete = ! $validator->fails();
        $status = $application->section_status ?? [];
        $status[$step] = $complete ? 'complete' : 'incomplete';
        $application->forceFill(['form' => $form, 'section_status' => $status, 'last_activity_at' => now()])->save();
        if ($complete && ($request->input('intent') === 'continue' || $request->expectsJson())) {
            $application->record('step.completed', ['step' => $step, 'step_title' => FormSteps::all()[$step]['title']], $request->user()->id);
        }
        if (in_array($step, ['secondary', 'post_secondary', 'english', 'tests', 'referees', 'study'], true)) {
            $this->checklist->refresh($application);
        }
        $this->stages->sync($application);

        if ($request->expectsJson()) {
            return response()->json(['saved' => true, 'complete' => $complete, 'errors' => $validator->errors()]);
        }

        if ($request->input('intent') === 'continue') {
            if (! $complete) {
                return back()->withErrors($validator)->withInput();
            }
            $keys = array_keys(FormSteps::all());
            $i = array_search($step, $keys, true);

            return isset($keys[$i + 1]) ? redirect()->route('portal.application.step', [$application, $keys[$i + 1]]) : redirect()->route('portal.dashboard')->with('status', 'Your application form is complete.');
        }

        return redirect()->route('portal.dashboard')->with('status', 'Saved. You can come back any time.');
    }

    public function withdraw(Request $request, Application $application)
    {
        $this->authorizeOwner($application);
        $request->validate(['confirm' => 'required|accepted']);
        $application->forceFill(['withdrawn_at' => now()])->save();
        $application->record('application.withdrawn', [], $request->user()->id);
        $application->reminders()->whereNull('sent_at')->update(['cancelled_at' => now()]);
        $this->stages->sync($application);

        return redirect()->route('portal.dashboard')->with('status', 'Your application has been withdrawn. Your documents will be deleted according to our retention policy.');
    }

    private function isLocked(Application $application): bool
    {
        return $application->authorisations()->whereNull('revoked_at')->exists() && $application->submissions()->whereIn('status', ['PACKAGE_READY', 'SUBMITTED'])->exists();
    }

    private function authorizeOwner(Application $application): void
    {
        abort_unless($application->user_id === auth()->id(), 403);
    }
}
