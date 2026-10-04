<?php

namespace Database\Seeders;

use App\Models\ChecklistRule;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Owner-approved service fees (2026-10-04), GBP in pence. They fill a price only where none is set, so a price
     * later changed by an admin (Admin → Services) is never reverted by a deploy's reference sync.
     */
    public const APPROVED_PRICES = ['T1' => 7500, 'T2' => 39500, 'T3' => 79500];

    public function run(): void
    {
        $tiers = [
            ['code' => 'T1', 'name' => 'Eligibility & Course Assessment', 'tagline' => 'Start with eligibility', 'badge' => null, 'sort' => 1, 'payment_gate' => 'AT_START',
                'summary' => 'A structured review of your Nigerian academic profile against the published requirements of UK medical schools, with a written assessment of which routes are open to you.',
                'deliverables' => ['Structured profile review by a qualified reviewer', 'Written assessment of which UK medicine routes appear open to you (standard, graduate, foundation), citing published requirements', 'Personalised requirements checklist', 'A list of relevant UK medical schools from our directory, with sources', 'One follow-up message thread with our team'],
                'exclusions' => ['University tuition and application fees', 'UCAT or GAMSAT fees', 'English tests', 'Visa and Immigration Health Surcharge', 'Document certification or translation']],
            ['code' => 'T2', 'name' => 'Medical Application Preparation', 'tagline' => 'Prepare your medical application', 'badge' => 'Most popular', 'sort' => 2, 'payment_gate' => 'AT_START',
                'summary' => 'Everything in the assessment, plus hands-on preparation of a complete application you then submit through the official route.',
                'deliverables' => ['Everything in Eligibility & Course Assessment', 'University shortlisting support based on published requirements and your preferences', 'Personal document checklist and review of every document you upload, with feedback', 'Structural feedback on your personal statement (we do not write it for you)', 'UCAT or GAMSAT planning guidance', 'Application-readiness review before you submit through UCAS or the university'],
                'exclusions' => ['Submission on your behalf', 'University tuition and application fees', 'UCAT or GAMSAT fees', 'English tests', 'Visa and Immigration Health Surcharge']],
            ['code' => 'T3', 'name' => 'Full Medical Application Support', 'tagline' => 'Full application support', 'badge' => null, 'sort' => 3, 'payment_gate' => 'AT_START',
                'summary' => 'Everything in preparation, plus the complete package, your recorded approval, submission support by the permitted route, and tracking until the university responds.',
                'deliverables' => ['Everything in Medical Application Preparation', 'Preparation of the complete application package', 'Student approval workflow before anything is submitted', 'Submission support by the route the university requires (guided UCAS, or direct where permitted)', 'Submission tracking and university correspondence support', 'Interview preparation guidance'],
                'exclusions' => ['Any guarantee of admission, scholarship or visa', 'University tuition and application fees', 'UCAT or GAMSAT fees', 'English tests', 'Visa and Immigration Health Surcharge']],
        ];
        foreach ($tiers as $t) {
            $tier = ServiceTier::updateOrCreate(['code' => $t['code']], $t);
            // One service fee per tier, paid before work begins. The display and the Stripe amount both read this row.
            $price = $tier->prices()->firstOrCreate(['component' => 'full', 'currency' => 'GBP'], ['amount_minor' => self::APPROVED_PRICES[$t['code']]]);
            if ($price->amount_minor === null) {
                $price->update(['amount_minor' => self::APPROVED_PRICES[$t['code']]]);
            }
            // T3 was once split into preparation and submission components that were never priced; retire them.
            TierPrice::where('service_tier_id', $tier->id)->whereIn('component', ['preparation', 'submission'])->whereNull('amount_minor')->update(['active' => false]);
        }

        $rules = [
            ['name' => 'Always: passport', 'predicate' => ['always' => true], 'require_codes' => ['PASSPORT'], 'reason_text' => 'Identity: your name on every document, on UCAS and on the visa must match your international passport exactly.', 'sort' => 1],
            ['name' => 'Always: personal statement', 'predicate' => ['always' => true], 'require_codes' => ['STATEMENT'], 'reason_text' => 'Every medical school requires a personal statement; we review its structure against the three UCAS questions.', 'sort' => 1],
            ['name' => 'WAEC sitting', 'predicate' => ['field' => 'secondary.sittings', 'op' => 'contains', 'key' => 'board', 'value' => 'WAEC'], 'require_codes' => ['WAEC'], 'reason_text' => 'You told us you sat WASSCE.', 'sort' => 2],
            ['name' => 'NECO sitting', 'predicate' => ['field' => 'secondary.sittings', 'op' => 'contains', 'key' => 'board', 'value' => 'NECO'], 'require_codes' => ['NECO'], 'reason_text' => 'You told us you sat NECO.', 'sort' => 3],
            ['name' => 'A-levels', 'predicate' => ['field' => 'post_secondary.items', 'op' => 'contains', 'key' => 'type', 'value' => 'ALEVEL'], 'require_codes' => ['ALEVEL'], 'reason_text' => 'You listed A-levels.', 'sort' => 4],
            ['name' => 'IB', 'predicate' => ['field' => 'post_secondary.items', 'op' => 'contains', 'key' => 'type', 'value' => 'IB'], 'require_codes' => ['IB'], 'reason_text' => 'You listed the IB Diploma.', 'sort' => 5],
            ['name' => 'Degree or graduate entry', 'predicate' => ['any' => [['field' => 'study.entry_type', 'op' => 'eq', 'value' => 'graduate'], ['field' => 'post_secondary.items', 'op' => 'contains', 'key' => 'type', 'value' => 'DEGREE']]], 'require_codes' => ['DEGREE_CERT', 'TRANSCRIPT', 'CV'], 'reason_text' => 'Graduate-entry and direct-application schools ask for your degree evidence and a CV.', 'sort' => 6],
            ['name' => 'English test taken', 'predicate' => ['all' => [['field' => 'english.route', 'op' => 'in', 'value' => ['IELTS', 'TOEFL', 'PTE']], ['field' => 'english.score', 'op' => 'truthy']]], 'require_codes' => ['ENGLISH'], 'reason_text' => 'You have an English test result.', 'sort' => 7],
            ['name' => 'UCAT taken', 'predicate' => ['field' => 'tests.ucat_status', 'op' => 'eq', 'value' => 'taken'], 'require_codes' => ['UCAT'], 'reason_text' => 'You have a UCAT result.', 'sort' => 8],
            ['name' => 'GAMSAT taken', 'predicate' => ['field' => 'tests.gamsat_status', 'op' => 'eq', 'value' => 'taken'], 'require_codes' => ['GAMSAT'], 'reason_text' => 'You have a GAMSAT result.', 'sort' => 9],
            ['name' => 'Referees given', 'predicate' => ['field' => 'referees.referees', 'op' => 'count_gte', 'value' => 1], 'require_codes' => ['REFERENCE'], 'reason_text' => 'A reference letter from the referee you named.', 'sort' => 10],
        ];
        ChecklistRule::where('name', 'Always: passport and statement')->delete(); // replaced by two rules with their own reasons (stage 31)
        foreach ($rules as $r) {
            ChecklistRule::updateOrCreate(['name' => $r['name']], $r + ['active' => true]);
        }
    }
}
