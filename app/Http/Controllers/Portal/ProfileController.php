<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $request->user()->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        // Sessions elsewhere (and remember-me cookies) made under the old password end; this one continues.
        Auth::logoutOtherDevices($data['password']);

        return back()->with('status', 'Your password has been changed and you have been signed out everywhere else.');
    }

    public function export(Request $request)
    {
        $user = $request->user()->load('applications.tier', 'applications.documents.versions', 'applications.events', 'applications.payments', 'applications.submissions', 'applications.messages', 'applications.authorisations');
        // Explicit allow-list: the student's own data only, never staff notes, storage paths or internal assignment fields.
        $applications = $user->applications->map(fn ($a) => [
            'application_number' => $a->application_number, 'intake_year' => $a->intake_year, 'service_tier' => $a->tier?->name, 'stage' => $a->stage->value,
            'completion_pct' => $a->completion_pct, 'form' => $a->form, 'section_status' => $a->section_status, 'created_at' => $a->created_at, 'withdrawn_at' => $a->withdrawn_at,
            'documents' => $a->documents->map(fn ($d) => ['code' => $d->code, 'title' => $d->title, 'status' => $d->status->value,
                'versions' => $d->versions->map(fn ($v) => ['version' => $v->version, 'original_filename' => $v->original_filename, 'mime' => $v->mime, 'size_bytes' => $v->size_bytes, 'uploaded_at' => $v->uploaded_at ?? $v->created_at])->values()])->values(),
            'events' => $a->events->map(fn ($e) => ['type' => $e->type, 'at' => $e->created_at])->values(),
            'payments' => $a->payments->map(fn ($p) => ['status' => $p->status, 'amount_minor' => $p->amount_minor, 'currency' => $p->currency, 'method' => $p->method, 'created_at' => $p->created_at, 'succeeded_at' => $p->succeeded_at])->values(),
            'submissions' => $a->submissions->map(fn ($s) => ['university_id' => $s->university_id, 'route_code' => $s->route_code, 'status' => $s->status, 'intake' => $s->intake, 'submitted_at' => $s->submitted_at])->values(),
            'messages' => $a->messages->map(fn ($m) => ['from_you' => $m->sender_user_id === $a->user_id, 'body' => $m->body, 'at' => $m->created_at])->values(),
            'approvals' => $a->authorisations->map(fn ($x) => ['typed_name' => $x->typed_name, 'declaration_version' => $x->declaration_version, 'approved_at' => $x->created_at, 'revoked_at' => $x->revoked_at])->values(),
        ])->values();
        $leads = Lead::where('user_id', $user->id)->orWhere('email', $user->email)->get()->map(fn ($l) => [
            'name' => $l->name, 'email' => $l->email, 'phone' => $l->phone, 'whatsapp' => $l->whatsapp, 'answers' => $l->eligibility_answers, 'result' => $l->eligibility_result, 'consent_marketing' => (bool) $l->consent_marketing, 'created_at' => $l->created_at,
        ])->values();
        $data = ['exported_at' => now()->toIso8601String(), 'user' => $user->only('name', 'email', 'phone', 'whatsapp', 'country', 'nigeria_state', 'created_at'), 'eligibility_checks' => $leads, 'applications' => $applications];

        return response()->streamDownload(fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'smukn-data-export-'.now()->format('Ymd').'.json', ['Content-Type' => 'application/json']);
    }

    public function requestDeletion(Request $request)
    {
        $request->validate(['confirm' => 'required|accepted']);
        // a durable record of the request (Admin → Students lists it; smukn:erase-account completes it)
        $request->user()->forceFill(['deletion_requested_at' => $request->user()->deletion_requested_at ?? now()])->save();
        $app = $request->user()->currentApplication();
        $app?->messages()->create(['sender_user_id' => $request->user()->id, 'body' => 'ACCOUNT DELETION REQUESTED by the student via their profile page.']);
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('Deletion request — '.$request->user()->email, ['The student requested account deletion. Check for legal hold (active submission), then run: php artisan smukn:erase-account <email> (within 30 days).'], $app ? route('admin.applications.show', $app) : null));

        return back()->with('status', 'We have received your deletion request and will complete it within 30 days unless a submission in progress requires us to retain records; we will tell you if so.');
    }
}
