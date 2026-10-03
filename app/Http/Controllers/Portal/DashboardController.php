<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ServiceTier;
use App\Services\Applications\ChecklistBuilder;
use App\Services\Applications\StageResolver;
use App\Support\Seo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private StageResolver $stages, private ChecklistBuilder $checklist) {}

    public function index(Request $request)
    {
        $user = $request->user()->load('applications.tier', 'applications.documents', 'applications.payments.tierPrice', 'applications.submissions.university', 'applications.authorisations');
        $application = $user->currentApplication();
        if (! $application) {
            return view('portal.start', ['seo' => Seo::make('Start your application')->noindex(), 'tiers' => ServiceTier::where('active', true)->with('prices')->orderBy('sort')->get()]);
        }
        $this->stages->sync($application);
        $application->load('events');
        [$label, $url, $kind] = $this->stages->nextAction($application);

        return view('portal.dashboard', ['seo' => Seo::make('Your dashboard')->noindex(), 'application' => $application, 'next' => compact('label', 'url', 'kind'), 'steps' => \App\Services\Applications\FormSteps::all()]);
    }

    public function start(Request $request)
    {
        $data = $request->validate(['service_tier_id' => 'required|exists:service_tiers,id', 'intake_year' => 'required|integer|min:2027|max:2030']);
        $user = $request->user();
        if ($user->currentApplication() && ! $user->currentApplication()->isTerminal()) {
            return redirect()->route('portal.dashboard');
        }
        $application = Application::create([
            'application_number' => Application::nextNumber((int) $data['intake_year']), 'user_id' => $user->id, 'service_tier_id' => $data['service_tier_id'],
            'intake_year' => $data['intake_year'], 'form' => ['study' => ['intake_year' => (int) $data['intake_year']]], 'section_status' => [], 'last_activity_at' => now(),
        ]);
        $application->record('application.started', ['tier' => $application->tier->name], $user->id);
        $this->checklist->refresh($application);
        $application->record('documents.generated', ['count' => $application->documents()->count()]);
        $this->stages->sync($application);
        $user->notify(new \App\Notifications\ApplicationNotification($application, 'application.started'));
        \App\Models\User::where('role', 'admin')->get()->each->notify(new \App\Notifications\StaffNotification('New application '.$application->application_number, [$user->name.' started '.$application->tier->name.' for '.$application->intake_year.' entry.'], route('admin.applications.show', $application)));

        return redirect()->route('portal.application.step', [$application, 'personal'])->with('status', 'Your application '.$application->application_number.' has been created. Everything you enter is saved automatically.');
    }
}
