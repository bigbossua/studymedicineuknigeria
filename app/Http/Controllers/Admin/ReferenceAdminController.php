<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\ReferenceFact;
use App\Models\University;
use App\Support\Seo;
use Illuminate\Http\Request;

/** Verification queue — the only place a fact becomes VERIFIED (docs/architecture/17.1, 52, 53). */
class ReferenceAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: ReferenceFact::VERIFY_ON_PAGE;
        $q = ReferenceFact::with('subject')->where('verification_status', $status)->orderBy('subject_type')->orderBy('subject_id')->orderBy('key');
        if ($k = $request->string('key')->toString()) {
            $q->where('key', $k);
        }

        return view('admin.reference.index', ['seo' => Seo::make('Verification queue')->noindex(), 'facts' => $q->paginate(50)->withQueryString(), 'status' => $status,
            'keys' => ReferenceFact::select('key')->distinct()->orderBy('key')->pluck('key'), 'counts' => ReferenceFact::selectRaw('verification_status, count(*) c')->groupBy('verification_status')->pluck('c', 'verification_status')]);
    }

    public function update(Request $request, ReferenceFact $fact)
    {
        $data = $request->validate([
            'decision' => 'required|in:verify,not_published,source_changed,archive,save',
            'value_text' => 'nullable|string|max:4000', 'value_number' => 'nullable|numeric', 'academic_year' => 'nullable|string|max:16',
            'source_url' => 'nullable|url|max:1024', 'notes' => 'nullable|string|max:2000',
        ]);
        $fact->fill(array_filter(['value_text' => $data['value_text'] ?? null, 'academic_year' => $data['academic_year'] ?? null, 'source_url' => $data['source_url'] ?? null, 'notes' => $data['notes'] ?? null], fn ($v) => $v !== null));
        if (array_key_exists('value_number', $data) && $data['value_number'] !== null) {
            $fact->value_number = $data['value_number'];
        }
        switch ($data['decision']) {
            case 'verify':
                if (! $fact->source_url) {
                    return back()->with('error', 'A fact cannot be verified without an official source URL.');
                }
                $fact->verification_status = ReferenceFact::VERIFIED;
                $fact->verified_at = now();
                $fact->verified_by = $request->user()->id;
                $fact->review_due_at = now()->addMonths(str_contains($fact->key, 'fee') || str_contains($fact->key, 'deadline') ? 6 : 12);
                break;
            case 'not_published': $fact->verification_status = ReferenceFact::NOT_PUBLISHED;
                $fact->verified_at = now();
                $fact->verified_by = $request->user()->id;
                break;
            case 'source_changed': $fact->verification_status = ReferenceFact::SOURCE_CHANGED;
                break;
            case 'archive': $fact->verification_status = ReferenceFact::ARCHIVED;
                break;
        }
        $fact->save();
        AdminAction::log('fact.'.$data['decision'], $fact, $data);

        return back()->with('status', 'Fact updated.');
    }

    public function universities()
    {
        return view('admin.reference.universities', ['seo' => Seo::make('Universities')->noindex(), 'universities' => University::withCount(['facts', 'facts as verified_count' => fn ($q) => $q->where('verification_status', ReferenceFact::VERIFIED)])->orderBy('name')->get()]);
    }

    public function publishUniversity(Request $request, University $university)
    {
        $data = $request->validate(['published' => 'required|boolean', 'summary' => 'nullable|string|max:2000', 'website_url' => 'nullable|url']);
        $university->update($data);
        AdminAction::log('university.publish', $university, $data);

        return back()->with('status', $university->name.' '.($university->published ? 'is now indexable' : 'set to noindex').'.');
    }
}
