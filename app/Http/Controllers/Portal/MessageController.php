<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $application->messages()->where('sender_user_id', '!=', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);

        return view('portal.messages', ['seo' => Seo::make('Messages')->noindex(), 'application' => $application, 'messages' => $application->messages()->with('sender')->get()]);
    }

    public function store(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $application->messages()->create(['sender_user_id' => $request->user()->id, 'body' => $data['body']]);
        $application->record('message.sent', [], $request->user()->id);
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('Message from student — '.$application->application_number, [Str::limit($data['body'], 300)], route('admin.applications.show', $application)));

        return back()->with('status', 'Message sent. We reply in your portal and by email.');
    }
}
