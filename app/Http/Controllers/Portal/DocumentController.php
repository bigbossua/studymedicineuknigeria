<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Services\Applications\DocumentCatalogue;
use App\Services\Applications\StageResolver;
use App\Services\Documents\DocumentStore;
use App\Support\Seo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private DocumentStore $store, private StageResolver $stages) {}

    public function index(Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $docs = $application->documents()->with('currentVersion')->get();

        return view('portal.documents.index', ['seo' => Seo::make('Your documents')->noindex(), 'application' => $application,
            'groups' => [
                'Required now' => $docs->filter(fn ($d) => $d->status->needsStudent()),
                'Received and under review' => $docs->filter(fn ($d) => in_array($d->status, [DocumentStatus::UPLOADED, DocumentStatus::UNDER_REVIEW], true)),
                'Accepted' => $docs->filter(fn ($d) => $d->status === DocumentStatus::ACCEPTED),
                'Not required for you' => $docs->filter(fn ($d) => $d->status === DocumentStatus::NOT_REQUIRED),
            ]]);
    }

    public function show(Application $application, Document $document)
    {
        abort_unless($application->user_id === auth()->id() && $document->application_id === $application->id, 403);

        return view('portal.documents.show', ['seo' => Seo::make($document->title)->noindex(), 'application' => $application, 'document' => $document->load('versions', 'events'), 'meta' => DocumentCatalogue::TYPES[$document->code] ?? DocumentCatalogue::TYPES['OTHER']]);
    }

    public function upload(Request $request, Application $application, Document $document)
    {
        abort_unless($application->user_id === auth()->id() && $document->application_id === $application->id, 403);
        abort_if($document->status === DocumentStatus::NOT_REQUIRED, 403);
        $request->validate(['file' => 'required|file|max:10240']);
        $version = $this->store->store($document, $request->file('file'), $request->user()->id);
        $application->record('document.uploaded', ['document_id' => $document->id, 'title' => $document->title, 'version' => $version->version], $request->user()->id);
        $this->stages->sync($application);
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('Document uploaded — '.$application->application_number, [$document->title.' v'.$version->version.' awaits review.'], route('admin.applications.show', $application)));

        return redirect()->route('portal.documents.index', $application)->with('status', $document->title.' received — under review.');
    }

    /** Authenticated, logged, streamed download. Never a public URL. */
    public function download(Request $request, Application $application, Document $document, DocumentVersion $version): StreamedResponse
    {
        $user = $request->user();
        abort_unless(($application->user_id === $user->id || $user->isStaff()) && $document->application_id === $application->id && $version->document_id === $document->id, 403);
        \DB::table('document_access_log')->insert(['document_version_id' => $version->id, 'user_id' => $user->id, 'ip' => $request->ip(), 'purpose' => 'download', 'created_at' => now()]);
        $bytes = $this->store->contents($version);

        return response()->streamDownload(fn () => print ($bytes), $version->original_filename, [
            'Content-Type' => $version->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Length' => strlen($bytes),
        ]);
    }
}
