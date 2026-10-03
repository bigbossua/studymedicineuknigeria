<?php

namespace App\Services\Applications;

/** Application form steps — docs/architecture/13.4. Validation rules per step; completion = passes rules. */
class FormSteps
{
    public static function all(): array
    {
        return [
            'personal' => ['title' => 'Personal details', 'intro' => 'Your name exactly as it appears in your international passport.'],
            'study' => ['title' => 'Intended study', 'intro' => 'What you want to study and when.'],
            'secondary' => ['title' => 'Secondary education', 'intro' => 'Your WAEC, NECO, Cambridge or other school-leaving results.'],
            'post_secondary' => ['title' => 'A-levels, IB, foundation or degree', 'intro' => 'Anything you studied after secondary school. Skip if none yet.'],
            'english' => ['title' => 'English language', 'intro' => 'Any English test you have taken or plan to take.'],
            'tests' => ['title' => 'Admissions tests', 'intro' => 'UCAT or GAMSAT, taken or planned.'],
            'experience' => ['title' => 'Experience and personal statement', 'intro' => 'Work, volunteering and your statement draft.'],
            'referees' => ['title' => 'Referees', 'intro' => 'Who can provide your academic reference.'],
            'declarations' => ['title' => 'Declarations', 'intro' => 'Accuracy, data protection and our terms.'],
        ];
    }

    public static function rules(string $step): array
    {
        return match ($step) {
            'personal' => [
                'legal_first_names' => 'required|string|max:120', 'legal_surname' => 'required|string|max:120',
                'date_of_birth' => 'required|date|before:-15 years', 'nationality' => 'required|string|max:64',
                'passport_number' => 'nullable|string|max:32', 'phone' => 'required|string|max:32', 'whatsapp' => 'nullable|string|max:32',
                'nigeria_state' => 'nullable|string|max:64', 'city' => 'nullable|string|max:120', 'country_of_residence' => 'required|string|max:64',
            ],
            'study' => [
                'course_family' => 'required|in:medicine,graduate_medicine,foundation_medicine,dentistry,other',
                'entry_type' => 'required|in:standard,graduate,foundation', 'intake_year' => 'required|integer|min:'.(now()->year + 1).'|max:'.(now()->year + 4),
                'preferred_universities' => 'nullable|array|max:4', 'preferred_universities.*' => 'string|max:120',
                'ucas_status' => 'required|in:not_started,started,submitted,offer',
            ],
            'secondary' => [
                'sittings' => 'required|array|min:1', 'sittings.*.board' => 'required|in:WAEC,NECO,CAMBRIDGE,IB,OTHER',
                'sittings.*.year' => 'required|integer|min:2005|max:'.(now()->year + 1), 'sittings.*.school' => 'nullable|string|max:160',
                'sittings.*.subjects' => 'required|array|min:3', 'sittings.*.subjects.*.subject' => 'required|string|max:64', 'sittings.*.subjects.*.grade' => 'required|string|max:8',
            ],
            'post_secondary' => [
                'none' => 'nullable|boolean', 'items' => 'required_without:none|array', 'items.*.type' => 'required|in:ALEVEL,IB,FOUNDATION,DEGREE,OTHER',
                'items.*.institution' => 'required|string|max:160', 'items.*.award' => 'nullable|string|max:120', 'items.*.result' => 'nullable|string|max:64',
                'items.*.start_year' => 'nullable|integer', 'items.*.end_year' => 'nullable|integer', 'items.*.status' => 'required|in:completed,in_progress,planned',
            ],
            'english' => [
                'route' => 'required|in:IELTS,TOEFL,PTE,UNIVERSITY_ASSESSMENT,WAEC_ENGLISH,NONE_YET',
                'score' => 'nullable|string|max:16', 'test_date' => 'nullable|date', 'planned_date' => 'nullable|date',
            ],
            'tests' => [
                'ucat_status' => 'required|in:taken,planned,not_planned', 'ucat_year' => 'nullable|integer', 'ucat_score' => 'nullable|integer|min:900|max:2700', 'ucat_sjt' => 'nullable|string|max:8',
                'gamsat_status' => 'required|in:taken,planned,not_planned', 'gamsat_score' => 'nullable|integer',
            ],
            'experience' => [
                'experience' => 'nullable|array', 'experience.*.role' => 'required|string|max:120', 'experience.*.organisation' => 'required|string|max:160', 'experience.*.hours' => 'nullable|integer', 'experience.*.summary' => 'nullable|string|max:600',
                'statement_status' => 'required|in:not_started,draft,final', 'statement_text' => 'nullable|string|max:4200',
            ],
            'referees' => [
                'referees' => 'required|array|min:1|max:2', 'referees.*.name' => 'required|string|max:120', 'referees.*.role' => 'required|string|max:120', 'referees.*.institution' => 'required|string|max:160', 'referees.*.email' => 'required|email',
                'consent_contact_referees' => 'required|accepted',
            ],
            'declarations' => [
                'accurate' => 'required|accepted', 'data_processing' => 'required|accepted', 'terms' => 'required|accepted', 'no_guarantee' => 'required|accepted',
            ],
            default => [],
        };
    }
}
