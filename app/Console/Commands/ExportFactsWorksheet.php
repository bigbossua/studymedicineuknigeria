<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\University;
use Illuminate\Console\Command;

/**
 * Writes the verification worksheet: every fact in the chosen statuses with its exact wording and official source URL,
 * so that a reviewer with a browser (the owner, or staff before the admin queue is deployed) can open each source,
 * record a decision and hand the CSV back to `smukn:facts-import`. Facts are addressed by a stable reference
 * (subject slug + key + academic year), never by database id, so the same worksheet applies to every environment.
 */
class ExportFactsWorksheet extends Command
{
    protected $signature = 'smukn:facts-export {file=data/verification/worksheet.csv} {--status=VERIFY-ON-PAGE,NOT_FOUND,REVIEW_DUE : comma-separated statuses to include}';

    protected $description = 'Export unverified facts with their sources as a verification worksheet (CSV)';

    public const HEADER = ['ref', 'priority', 'subject', 'key', 'academic_year', 'current_value', 'source_url', 'status', 'notes', 'decision', 'verified_value', 'new_source_url', 'reviewer_note', 'verified_on'];

    public static function ref(ReferenceFact $f): string
    {
        $subject = $f->subject;
        $slug = match (true) {
            $subject instanceof Course => ($subject->university?->slug ?? 'course').'/'.$subject->slug,
            $subject instanceof University, $subject instanceof Topic => $subject->slug,
            default => (string) $f->subject_id,
        };

        return strtolower(class_basename($f->subject_type)).':'.$slug.':'.$f->key.($f->academic_year ? ':'.$f->academic_year : '');
    }

    public function handle(): int
    {
        $statuses = array_filter(array_map('trim', explode(',', (string) $this->option('status'))));
        $facts = ReferenceFact::with(['subject' => fn ($m) => $m->morphWith([Course::class => ['university']])])->whereIn('verification_status', $statuses)->get()
            ->sortBy(fn ($f) => [self::priority($f), class_basename($f->subject_type), $f->subject?->name ?? $f->subject?->title ?? '', $f->key])->values();
        $path = base_path($this->argument('file'));
        @mkdir(dirname($path), 0755, true);
        $h = fopen($path, 'w');
        fputcsv($h, self::HEADER, ',', '"', '');
        foreach ($facts as $f) {
            $value = $f->value_text ?? ($f->value_number !== null ? (string) (float) $f->value_number : ($f->value_bool === null ? '' : ($f->value_bool ? 'yes' : 'no')));
            $subject = $f->subject instanceof Course ? ($f->subject->university?->name.' · '.$f->subject->title) : ($f->subject?->name ?? $f->subject?->title ?? '');
            fputcsv($h, [self::ref($f), self::priority($f), $subject, $f->key, $f->academic_year, $value, $f->source_url, $f->verification_status, $f->notes, '', '', '', '', ''], ',', '"', '');
        }
        fclose($h);
        $this->info("{$facts->count()} fact(s) written to {$this->argument('file')} (priority 1 = dates and fees students act on).");

        return self::SUCCESS;
    }

    /** 1 = cycle dates, fees and visa figures; 2 = Nigerian-applicant statements; 3 = everything else. */
    public static function priority(ReferenceFact $f): int
    {
        if ($f->subject instanceof Topic || str_contains($f->key, 'fee') || str_contains($f->key, 'deadline')) {
            return 1;
        }
        if (in_array($f->key, ['waec_neco_statement', 'english_requirement', 'english_language_requirement', 'foundation_route', 'gem_international', 'international_places_open', 'international_accepted', 'a_level_requirement', 'admissions_test', 'application_route'], true)) {
            return 2;
        }

        return 3;
    }
}
