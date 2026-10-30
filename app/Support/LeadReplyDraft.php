<?php

namespace App\Support;

use App\Models\Lead;
use Illuminate\Support\Facades\Route;

/**
 * A reply a member of staff can review, edit and send themselves to an eligibility-checker lead. It is built only from
 * the lead's own answers and the cautious route assessment the site already showed them, so it adds no requirement,
 * date or promise of its own. Nothing is sent from here; the draft lives behind the staff two-step sign-in, so the
 * lead's details never leave the server (no log, no report).
 */
final class LeadReplyDraft
{
    private const ANSWERS = [
        'qualification' => ['label' => 'Your qualifications', 'values' => ['waec_only' => 'WAEC or NECO (WASSCE) only', 'alevels_ib' => 'A-levels or the IB', 'foundation' => 'a foundation programme', 'nigerian_degree' => 'a Nigerian degree', 'other' => 'other qualifications']],
        'sciences' => ['label' => 'Sciences', 'values' => ['yes' => 'Chemistry and Biology', 'partial' => 'some of the required sciences', 'no' => 'no sciences yet']],
        'english' => ['label' => 'English evidence', 'values' => ['ielts' => 'IELTS (or an equivalent test)', 'waec_english' => 'WAEC or NECO English', 'none' => 'none yet']],
        'ucat' => ['label' => 'UCAT', 'values' => ['taken' => 'taken', 'planned' => 'planned', 'none' => 'not planned yet']],
        'intake_year' => ['label' => 'Intended start', 'values' => []],
    ];

    private const STATUS = ['open' => 'appears open', 'possible' => 'possible', 'conditional' => 'depends on conditions', 'closed' => 'not open'];

    /** @return array{subject: string, body: string} */
    public static function for(Lead $lead): array
    {
        $answers = $lead->eligibility_answers ?? [];
        $result = $lead->eligibility_result ?? [];
        $first = trim(strtok((string) $lead->name, ' ') ?: '') ?: 'there';

        $lines = ["Dear {$first},", ''];
        $lines[] = 'Thank you for using the eligibility checker on Study Medicine UK Nigeria on '.$lead->created_at->format('j F Y').'.'
            .($lead->isOverdue() ? ' I am sorry this reply has taken longer than the two working days we promise.' : '');
        if ($answers) {
            $lines[] = '';
            $lines[] = 'You told us:';
            foreach (self::ANSWERS as $key => $spec) {
                if (isset($answers[$key])) {
                    $lines[] = '- '.$spec['label'].': '.($spec['values'][$answers[$key]] ?? $answers[$key]);
                }
            }
        }
        if (! empty($result['routes'])) {
            $lines[] = '';
            $lines[] = 'Based on the requirements universities publish (this is not an admissions decision; only universities decide):';
            foreach ($result['routes'] as [$route, $status, $why]) {
                $lines[] = '- '.$route.' ('.(self::STATUS[$status] ?? $status).'): '.$why;
            }
        }
        $reads = collect($result['reads'] ?? [])->filter(fn ($r) => Route::has($r))->map(fn ($r) => '- '.route($r))->all();
        if ($reads) {
            $lines[] = '';
            $lines[] = 'These pages set out each school\'s published requirements:';
            array_push($lines, ...$reads);
        }
        $lines[] = '';
        $lines[] = 'If you would like us to look at your situation in more detail, reply to this email with your results so far, or create an account at '.route('register').' to start an application profile. We cannot guarantee an offer from any university.';
        $lines[] = '';
        $lines[] = 'Kind regards,';
        $lines[] = '[your name]';
        $lines[] = config('site.name');

        return ['subject' => 'Your UK Medicine eligibility check', 'body' => implode("\n", $lines)];
    }
}
