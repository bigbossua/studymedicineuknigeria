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
        if (($data['decision'] === 'verify') && ! $fact->source_url) {
            return back()->with('error', 'A fact cannot be verified without an official source URL.');
        }
        // Editing the value, source or year of a verified fact makes it unverified again: the new wording has not been read
        // on the page. Only an explicit "verify" keeps it published.
        if ($data['decision'] === 'save' && $fact->verification_status === ReferenceFact::VERIFIED && $fact->isDirty(['value_text', 'value_number', 'academic_year', 'source_url'])) {
            $fact->verification_status = ReferenceFact::VERIFY_ON_PAGE;
        }
        $this->applyDecision($fact, $data['decision'], $request->user()->id);
        $fact->reviewed_at = now();
        $fact->save();
        AdminAction::log('fact.'.$data['decision'], $fact, $data);

        return back()->with('status', 'Fact updated.');
    }

    /** Shared status transition for single and bulk decisions. Returns false when the decision is not applicable. */
    private function applyDecision(ReferenceFact $fact, string $decision, int $userId): bool
    {
        switch ($decision) {
            case 'verify':
                if (! $fact->source_url) {
                    return false;
                }
                $fact->verification_status = ReferenceFact::VERIFIED;
                $fact->verified_at = now();
                $fact->verified_by = $userId;
                $fact->review_due_at = now()->addMonths(str_contains($fact->key, 'fee') || str_contains($fact->key, 'deadline') ? 6 : 12);
                break;
            case 'not_published':
                $fact->verification_status = ReferenceFact::NOT_PUBLISHED;
                $fact->verified_at = now();
                $fact->verified_by = $userId;
                break;
            case 'source_changed':
                $fact->verification_status = ReferenceFact::SOURCE_CHANGED;
                break;
            case 'archive':
                $fact->verification_status = ReferenceFact::ARCHIVED;
                break;
        }

        return true;
    }

    /**
     * The fastest way through the backlog: one official page open, every fact taken from it
     * verified together. Sources ordered by how many pending facts they unlock.
     */
    public function sources(Request $request)
    {
        $pending = [ReferenceFact::VERIFY_ON_PAGE, ReferenceFact::REVIEW_DUE, ReferenceFact::SOURCE_CHANGED];
        $sources = ReferenceFact::selectRaw('source_url, count(*) as c')->whereIn('verification_status', $pending)->whereNotNull('source_url')
            ->groupBy('source_url')->orderByDesc('c')->orderBy('source_url')->paginate(20)->withQueryString();
        $facts = ReferenceFact::with('subject')->whereIn('verification_status', $pending)->whereIn('source_url', $sources->pluck('source_url'))
            ->orderBy('subject_type')->orderBy('subject_id')->orderBy('key')->get()->groupBy('source_url');

        return view('admin.reference.sources', ['seo' => Seo::make('Verify by source')->noindex(), 'sources' => $sources, 'facts' => $facts,
            'pendingTotal' => ReferenceFact::whereIn('verification_status', $pending)->count(), 'withoutSource' => ReferenceFact::whereIn('verification_status', $pending)->whereNull('source_url')->count()]);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate(['decision' => 'required|in:verify,not_published', 'fact_ids' => 'required|array|min:1|max:200', 'fact_ids.*' => 'integer|exists:reference_facts,id']);
        $done = 0;
        $skipped = 0;
        ReferenceFact::whereIn('id', $data['fact_ids'])->get()->each(function (ReferenceFact $fact) use ($data, $request, &$done, &$skipped) {
            if ($this->applyDecision($fact, $data['decision'], $request->user()->id)) {
                $fact->reviewed_at = now();
                $fact->save();
                $done++;
            } else {
                $skipped++;
            }
        });
        AdminAction::log('fact.bulk_'.$data['decision'], null, ['count' => $done, 'skipped' => $skipped, 'fact_ids' => $data['fact_ids']]);

        return back()->with('status', "{$done} fact(s) marked ".($data['decision'] === 'verify' ? 'verified' : 'not published').($skipped ? "; {$skipped} skipped (no source URL)." : '.'));
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
