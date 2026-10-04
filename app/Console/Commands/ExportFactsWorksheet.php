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
    protected $signature = 'smukn:facts-export {file=data/verification/worksheet.csv} {--status=VERIFY-ON-PAGE,NOT_FOUND,REVIEW_DUE,SOURCE_CHANGED : comma-separated statuses to include} {--sources= : also write a one-row-per-official-page summary (the browsing order) to this path}';

    protected $description = 'Export unverified facts with their sources as a verification worksheet (CSV)';

    public const HEADER = ['ref', 'priority', 'priority_area', 'source_group', 'facts_on_source', 'subject', 'key', 'academic_year', 'current_value', 'source_url', 'status', 'notes', 'decision', 'verified_value', 'new_source_url', 'reviewer_note', 'verified_on'];

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
            ->values();
        // One official page often answers several facts: keep them together. Each source is placed at the highest
        // priority any of its facts has, so opening it once resolves every fact on it.
        $bySource = $facts->groupBy(fn ($f) => $f->source_url ?: 'no-source:'.$f->id);
        $sourceTier = $bySource->map(fn ($g) => $g->min(fn ($f) => self::priority($f)));
        $facts = $facts->sortBy(fn ($f) => [$sourceTier[$f->source_url ?: 'no-source:'.$f->id], $f->source_url ?: 'zzz', self::priority($f), $f->subject?->name ?? $f->subject?->title ?? '', $f->key])->values();
        $groups = $facts->map(fn ($f) => $f->source_url ?: 'no-source:'.$f->id)->unique()->values()->flip();
        $path = base_path($this->argument('file'));
        @mkdir(dirname($path), 0755, true);
        $dir = realpath(dirname($path));
        if (! $dir || ! str_starts_with($dir.DIRECTORY_SEPARATOR, realpath(base_path()).DIRECTORY_SEPARATOR)) {
            $this->error('The worksheet must be written inside the project directory.');

            return self::FAILURE;
        }
        $h = fopen($path, 'w');
        fputcsv($h, self::HEADER, ',', '"', '');
        foreach ($facts as $f) {
            $value = $f->value_text ?? ($f->value_number !== null ? (string) (float) $f->value_number : ($f->value_bool === null ? '' : ($f->value_bool ? 'yes' : 'no')));
            $subject = $f->subject instanceof Course ? ($f->subject->university?->name.' · '.$f->subject->title) : ($f->subject?->name ?? $f->subject?->title ?? '');
            $src = $f->source_url ?: 'no-source:'.$f->id;
            [$tier, $area] = self::tier($f);
            fputcsv($h, array_map([self::class, 'cell'], [self::ref($f), $tier, $area, 'S'.str_pad((string) ($groups[$src] + 1), 3, '0', STR_PAD_LEFT), $bySource[$src]->count(), $subject, $f->key, $f->academic_year, $value, $f->source_url, $f->verification_status, $f->notes, '', '', '', '', '']), ',', '"', '');
        }
        fclose($h);
        if ($sourcesPath = $this->option('sources')) {
            $sp = base_path($sourcesPath);
            $dir = realpath(dirname($sp));
            if (! $dir || ! str_starts_with($dir.DIRECTORY_SEPARATOR, realpath(base_path()).DIRECTORY_SEPARATOR)) {
                $this->error('The sources sheet must be written inside the project directory.');

                return self::FAILURE;
            }
            $s = fopen($sp, 'w');
            fputcsv($s, ['source_group', 'priority', 'priority_area', 'facts', 'statuses', 'source_url', 'subjects', 'refs'], ',', '"', '');
            foreach ($groups as $src => $i) {
                $g = $bySource[$src];
                $top = $g->sortBy(fn ($f) => self::priority($f))->first();
                [$tier, $area] = self::tier($top);
                $subjects = $g->map(fn ($f) => $f->subject instanceof Course ? $f->subject->university?->name : ($f->subject?->name ?? $f->subject?->title))->filter()->unique()->implode('; ');
                fputcsv($s, array_map([self::class, 'cell'], ['S'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), $tier, $area, $g->count(), $g->countBy('verification_status')->map(fn ($n, $st) => "{$st} {$n}")->implode('; '),
                    str_starts_with($src, 'no-source:') ? '' : $src, $subjects, $g->map(fn ($f) => self::ref($f))->implode(' ')]), ',', '"', '');
            }
            fclose($s);
            $this->info(count($groups)." official source(s) written to {$sourcesPath}.");
        }
        $this->info("{$facts->count()} fact(s) written to {$this->argument('file')} (priority 1 = dates and fees students act on).");

        return self::SUCCESS;
    }

    /** 1 = cycle dates, fees and visa figures; 2 = Nigerian-applicant statements; 3 = everything else. */
    /** Spreadsheet formula injection: the owner opens this file in Excel or Sheets, so no cell may start a formula. */
    public static function cell(mixed $v): mixed
    {
        return is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$v : $v;
    }

    /**
     * Review order set by the owner (2026-10-04): the facts students act on first. Returns [tier, area].
     *
     * @return array{0: int, 1: string}
     */
    public static function tier(ReferenceFact $f): array
    {
        $s = $f->subject;
        $k = $f->key;
        if ($s instanceof Course && ($s->profession ?? 'medicine') !== 'medicine') {
            return [12, 'Other healthcare subjects'];
        }
        if ($s instanceof Topic) {
            return match (true) {
                str_starts_with($s->slug, 'ucas') && str_contains($k, 'deadline') => [1, 'UCAS Medicine deadlines'],
                str_starts_with($s->slug, 'ucat') => [2, 'UCAT dates and rules'],
                $s->slug === 'gmc-registration' => [3, 'GMC status and registration'],
                str_starts_with($s->slug, 'costs') => [6, 'International fees and costs'],
                $s->slug === 'student-visa' => [7, 'Visa and immigration'],
                $s->slug === 'graduate-visa' => [8, 'Graduate and work rules'],
                str_starts_with($s->slug, 'ucas') => [9, 'Application routes'],
                default => [11, 'Other'],
            };
        }

        return match (true) {
            $k === 'gmc_status' => [3, 'GMC status and registration'],
            in_array($k, ['international_accepted', 'international_places', 'international_places_open', 'gem_international'], true) => [4, 'Medical school eligibility'],
            in_array($k, ['waec_neco_statement', 'english_requirement', 'english_language_requirement', 'a_level_requirement', 'foundation_route'], true) => [5, 'Nigerian qualifications and entry'],
            str_contains($k, 'fee') => [6, 'International fees and costs'],
            in_array($k, ['application_route', 'admissions_test', 'interview_format', 'intake_month'], true) => [9, 'Application routes'],
            in_array($k, ['ucas_code', 'course_length_years', 'graduate_entry_course', 'msc_member'], true) => [10, 'Course availability'],
            default => [11, 'Other'],
        };
    }

    public static function priority(ReferenceFact $f): int
    {
        return self::tier($f)[0];
    }
}
