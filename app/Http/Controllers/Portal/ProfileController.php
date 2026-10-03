<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('portal.profile', ['seo' => Seo::make('Your profile')->noindex(), 'user' => $request->user(), 'application' => $request->user()->currentApplication()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:160', 'phone' => 'nullable|string|max:32', 'whatsapp' => 'nullable|string|max:32', 'nigeria_state' => 'nullable|string|max:64']);
        $request->user()->update($data);

        return back()->with('status', 'Your details have been updated.');
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('status', 'Your password has been changed.');
    }

    public function export(Request $request)
    {
        $user = $request->user()->load('applications.documents.versions', 'applications.events', 'applications.payments', 'applications.submissions.events', 'applications.messages', 'applications.authorisations');
        $data = ['exported_at' => now()->toIso8601String(), 'user' => $user->only('name', 'email', 'phone', 'whatsapp', 'country', 'nigeria_state', 'created_at'), 'applications' => $user->applications->toArray()];

        return response()->streamDownload(fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'smukn-data-export-'.now()->format('Ymd').'.json', ['Content-Type' => 'application/json']);
    }

    public function requestDeletion(Request $request)
    {
        $request->validate(['confirm' => 'required|accepted']);
        $app = $request->user()->currentApplication();
        $app?->messages()->create(['sender_user_id' => $request->user()->id, 'body' => 'ACCOUNT DELETION REQUESTED by the student via their profile page.']);
        \App\Models\User::where('role', 'admin')->get()->each->notify(new \App\Notifications\StaffNotification('Deletion request — '.$request->user()->email, ['The student requested account deletion. Check for legal hold (active submission) and action within 30 days.'], $app ? route('admin.applications.show', $app) : null));

        return back()->with('status', 'We have received your deletion request and will complete it within 30 days unless a submission in progress requires us to retain records; we will tell you if so.');
    }
}
