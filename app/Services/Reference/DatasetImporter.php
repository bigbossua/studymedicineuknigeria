<?php

namespace App\Services\Reference;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\University;
use App\Support\FactSeeding;
use Illuminate\Support\Str;

/**
 * Imports the research datasets in /data into universities, courses and reference_facts.
 * Idempotent: re-running updates existing rows (matched by university slug + fact key + academic year + qualification).
 * Never upgrades a status to VERIFIED — only humans do that in the admin verification queue.
 */
class DatasetImporter
{
    public array $log = [];

    /** Readable names for joint or renamed institutions (the dataset keeps the long legal form). */
    public const DISPLAY_NAMES = [
        'hull-york' => 'Hull York Medical School',
        'kent-medway' => 'Kent and Medway Medical School',
        'brighton-sussex' => 'Brighton and Sussex Medical School',
        'scotgem' => 'ScotGEM (St Andrews and Dundee)',
        'pears-cumbria' => 'Pears Cumbria School of Medicine',
        'greater-manchester' => 'University of Greater Manchester',
        'lancashire' => 'University of Lancashire',
        'lincoln' => 'University of Lincoln',
        'st-georges-london' => "City St George's, University of London",
    ];

    public function run(string $dataPath): array
    {
        $this->importSchools("$dataPath/medical-schools/schools.json");
        $this->importFees("$dataPath/medical-schools/fees.json");
        $this->importStatements("$dataPath/qualifications/university-statements.json");

        return $this->log;
    }

    /** Normalise names so the three datasets join: strip "(UCL)" style suffixes, "The", "University of". */
    public static function slugFor(string $name): string
    {
        $joint = [
            'hull and university of york' => 'hull-york', 'hull york' => 'hull-york',
            'kent and canterbury christ church' => 'kent-medway', 'kent and medway' => 'kent-medway',
            'brighton and university of sussex' => 'brighton-sussex', 'brighton and sussex' => 'brighton-sussex',
            'imperial college london (degree-awarding) with university of cumbria' => 'pears-cumbria', 'cumbria' => 'pears-cumbria',
            'st andrews and university of dundee' => 'scotgem', 'scotgem' => 'scotgem',
            "city st george's" => 'st-georges-london', 'city st georges' => 'st-georges-london',
            'brunel' => 'brunel', 'lincoln' => 'lincoln',
        ];
        $low = Str::lower(str_replace('’', "'", $name));
        foreach ($joint as $needle => $slug) {
            if (str_contains($low, $needle)) {
                return $slug;
            }
        }
        $n = preg_replace('/\s*\((.*?)\)\s*/', ' ', $name);
        $n = str_ireplace(['The University of ', 'University of ', ' University', 'The '], ['', '', '', ''], $n);
        $aliases = [
            'queen mary london' => 'queen-mary-london', 'barts and the london' => 'queen-mary-london', 'queen mary' => 'queen-mary-london',
            'kings college london' => 'kings-college-london', "king's college london" => 'kings-college-london',
            'university college london' => 'ucl', 'ucl' => 'ucl',
            'hull york medical school' => 'hull-york', 'hull york' => 'hull-york',
            'brighton and sussex medical school' => 'brighton-sussex', 'brighton and sussex' => 'brighton-sussex',
            'kent and medway medical school' => 'kent-medway', 'kent and medway' => 'kent-medway',
            'queens belfast' => 'queens-belfast', "queen's belfast" => 'queens-belfast', "queen's university belfast" => 'queens-belfast',
            'central lancashire' => 'lancashire', 'uclan' => 'lancashire', 'lancashire' => 'lancashire',
            'greater manchester' => 'greater-manchester', 'bolton' => 'greater-manchester',
            'east anglia' => 'east-anglia', 'uea' => 'east-anglia',
            'st andrews' => 'st-andrews', 'st georges' => 'st-georges-london', "st george's" => 'st-georges-london', 'city st georges' => 'st-georges-london', "city st george's" => 'st-georges-london',
            'anglia ruskin' => 'anglia-ruskin', 'edge hill' => 'edge-hill',
            'imperial college london' => 'imperial', 'imperial college' => 'imperial', 'imperial' => 'imperial',
            'wolverhampton' => 'wolverhampton', 'black country medical school' => 'wolverhampton',
            'three counties medical school' => 'worcester', 'worcester' => 'worcester',
            'pears cumbria medical school' => 'pears-cumbria', 'pears cumbria' => 'pears-cumbria',
            'scotgem' => 'scotgem', 'st marys twickenham' => 'st-marys-twickenham', "st mary's twickenham" => 'st-marys-twickenham', "st mary's" => 'st-marys-twickenham',
        ];
        $key = Str::lower(trim(preg_replace('/\s+/', ' ', $n)));
        $key = str_replace(['’', 'medical school'], ["'", ''], $key);
        $key = trim($key);
        foreach ($aliases as $alias => $slug) {
            if ($key === $alias || Str::startsWith($key, $alias.' ')) {
                return $slug;
            }
        }

        return Str::slug($key);
    }

    private function val(array $row, string $field): array
    {
        $f = $row[$field] ?? null;
        if (! is_array($f)) {
            return [$f, null, $f === null ? ReferenceFact::NOT_FOUND : ReferenceFact::VERIFY_ON_PAGE, null];
        }
        $status = match ($f['status'] ?? null) {
            'VERIFY-ON-PAGE' => ReferenceFact::VERIFY_ON_PAGE,
            'NOT PUBLISHED', 'NOT_PUBLISHED' => ReferenceFact::NOT_PUBLISHED,
            default => ReferenceFact::NOT_FOUND,
        };

        return [$f['value'] ?? null, $f['source'] ?? null, $status, $f['source_type'] ?? null];
    }

    private function fact($subject, string $key, $value, ?string $source, string $status, ?string $sourceType = null, array $extra = []): void
    {
        if ($value === null && $status === ReferenceFact::NOT_FOUND) {
            return; // nothing to record; absence is not a fact
        }
        $attrs = ['value_text' => null, 'value_number' => null, 'value_bool' => null, 'value_json' => null];
        if (is_bool($value)) {
            $attrs['value_bool'] = $value;
        } elseif (is_int($value) || is_float($value)) {
            $attrs['value_number'] = $value;
        } elseif (is_array($value)) {
            $attrs['value_json'] = $value;
        } elseif ($value !== null) {
            $attrs['value_text'] = (string) $value;
        }

        $match = ['key' => $key, 'academic_year' => $extra['academic_year'] ?? null, 'qualification_code' => $extra['qualification_code'] ?? null];
        $payload = $attrs + [
            'applies_to' => $extra['applies_to'] ?? 'international',
            'source_url' => $source,
            'source_type' => $sourceType,
            'notes' => $extra['notes'] ?? null,
        ];
        FactSeeding::upsert($subject, $match, $payload, $status);
    }

    private function importSchools(string $file): void
    {
        $rows = json_decode(file_get_contents($file), true)['schools'] ?? [];
        foreach ($rows as $row) {
            [$name] = $this->val($row, 'university');
            if (! $name) {
                continue;
            }
            $slug = self::slugFor($name);
            $name = self::DISPLAY_NAMES[$slug] ?? $name;
            [$msName] = $this->val($row, 'medical_school_name');
            [$city] = $this->val($row, 'city');
            [$nation] = $this->val($row, 'nation');
            [$msc] = $this->val($row, 'msc_member');
            [$gmc] = $this->val($row, 'gmc_status');
            [$intl, , $intlStatus] = $this->val($row, 'international_accepted');
            [$onlyFlag] = $this->val($row, 'international_only_or_home_only');
            $flag = is_string($onlyFlag) ? Str::lower($onlyFlag) : '';
            $policy = match (true) {
                str_starts_with($flag, 'international-only'), str_starts_with($flag, 'international only') => 'international_only',
                str_starts_with($flag, 'home-only'), str_starts_with($flag, 'home only') => 'home_only',
                $intl === true => 'accepts',
                $intl === false => 'home_only',
                default => 'not_published',
            };

            $u = University::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'medical_school_name' => $msName,
                'city' => $city,
                'nation' => $nation,
                'msc_member' => is_bool($msc) ? $msc : null,
                'gmc_status' => $gmc,
                'international_policy' => $policy,
            ]);

            foreach (['international_accepted', 'international_places', 'gmc_status', 'msc_member', 'english_language_requirement', 'notes'] as $k) {
                [$v, $src, $st, $type] = $this->val($row, $k);
                if ($k === 'notes' && is_string($v)) {
                    $this->fact($u, 'research_notes', $v, null, ReferenceFact::VERIFY_ON_PAGE, null, ['applies_to' => 'all']);

                    continue;
                }
                $this->fact($u, $k, $v, $src, $st, $type, ['applies_to' => $k === 'english_language_requirement' ? 'international' : 'all']);
            }

            [$courseName, $cSrc] = $this->val($row, 'main_ug_course_name');
            [$ucas] = $this->val($row, 'ucas_code');
            [$len] = $this->val($row, 'course_length_years');
            [$test] = $this->val($row, 'admissions_test');
            [$route] = $this->val($row, 'application_route');
            [$url] = $this->val($row, 'official_course_url');
            $title = $courseName ?: 'Medicine';
            $course = Course::updateOrCreate(['university_id' => $u->id, 'slug' => $ucas ? Str::lower($ucas) : 'medicine'], [
                'title' => $title,
                'award' => $this->awardFrom($title),
                'ucas_code' => $ucas,
                'entry_type' => 'standard',
                'length_years' => is_numeric($len) ? (int) $len : null,
                'application_route' => $this->routeFrom($route),
                'admissions_test' => $this->testFrom($test),
                'official_url' => $url ?: $cSrc,
            ]);
            foreach (['ucas_code', 'course_length_years', 'admissions_test', 'interview_format', 'application_route', 'intake_month', 'graduate_entry_course', 'international_fee_per_year_gbp'] as $k) {
                [$v, $src, $st, $type] = $this->val($row, $k);
                $key = $k === 'international_fee_per_year_gbp' ? 'international_fee_gbp' : $k;
                $extra = $key === 'international_fee_gbp' ? ['academic_year' => '2026/27', 'notes' => 'From schools dataset; fee year assumed 2026/27 — confirm.'] : ['applies_to' => 'all'];
                if ($key === 'international_fee_gbp' && is_string($v)) {
                    $v = (float) preg_replace('/[^\d.]/', '', $v) ?: null;
                }
                $this->fact($course, $key, $v, $src, $st, $type, $extra);
            }
            $this->log[] = "school: {$u->name} ({$slug}) policy={$policy}";
        }
    }

    private function importFees(string $file): void
    {
        $rows = json_decode(file_get_contents($file), true) ?? [];
        foreach ($rows as $row) {
            $slug = self::slugFor($row['university']);
            $u = University::firstWhere('slug', $slug);
            if (! $u) {
                $this->log[] = "fees: no university for {$row['university']} -> $slug (created)";
                $u = University::create(['slug' => $slug, 'name' => $row['university'], 'international_policy' => 'not_published']);
            }
            $isGem = (bool) preg_match('/A101|A102|graduate/i', $row['course'] ?? '');
            $course = $u->courses()->where('entry_type', $isGem ? 'graduate' : 'standard')->first()
                ?? Course::create(['university_id' => $u->id, 'slug' => $isGem ? 'graduate-entry' : 'medicine', 'title' => $row['course'] ?? 'Medicine', 'entry_type' => $isGem ? 'graduate' : 'standard', 'award' => $this->awardFrom($row['course'] ?? '')]);
            $status = match ($row['status'] ?? '') {
                'NOT OPEN TO INTERNATIONAL' => ReferenceFact::NOT_PUBLISHED,
                'NOT FOUND IN THIS SESSION' => ReferenceFact::NOT_FOUND,
                'NOT PUBLISHED' => ReferenceFact::NOT_PUBLISHED,
                default => ReferenceFact::VERIFY_ON_PAGE,
            };
            $sourceType = str_starts_with($row['status'] ?? '', 'LEAD') ? 'lead' : (str_starts_with($row['status'] ?? '', 'OFFICIAL') ? 'official' : null);
            $this->fact($course, 'international_fee_gbp', $row['fee_gbp'] ?? null, $row['source'] ?? null, $status, $sourceType, [
                'academic_year' => $row['fee_year'] ?? null,
                'notes' => trim(($row['notes'] ?? '').(($row['status'] ?? '') ? ' ['.$row['status'].']' : '')),
            ]);
            if (array_key_exists('clinical_years_differ', $row) && $row['clinical_years_differ'] !== null) {
                $this->fact($course, 'clinical_years_fee_differs', (bool) $row['clinical_years_differ'], $row['source'] ?? null, $status, $sourceType, ['academic_year' => $row['fee_year'] ?? null]);
            }
            if (($row['status'] ?? '') === 'NOT OPEN TO INTERNATIONAL' && $u->international_policy !== 'home_only') {
                $u->update(['international_policy' => 'home_only']);
            }
        }
    }

    private function importStatements(string $file): void
    {
        $rows = json_decode(file_get_contents($file), true) ?? [];
        foreach ($rows as $row) {
            $slug = self::slugFor($row['university']);
            $u = University::firstWhere('slug', $slug);
            if (! $u) {
                $this->log[] = "statements: no university for {$row['university']} -> $slug (created)";
                $u = University::create(['slug' => $slug, 'name' => $row['university'], 'international_policy' => 'not_published']);
            }
            $src = $row['sources'][0] ?? null;
            $status = ($row['status'] ?? '') === 'VERIFY-ON-PAGE' ? ReferenceFact::VERIFY_ON_PAGE : ReferenceFact::NOT_FOUND;
            $cycle = isset($row['entry_cycle']) ? $row['entry_cycle'].' entry' : null;
            $map = [
                'waec_neco_statement' => ['WASSCE', 'international'],
                'a_level_requirement' => ['ALEVEL', 'all'],
                'gem_international' => ['DEGREE', 'international'],
                'english_requirement' => ['ENGLISH', 'international'],
                'foundation_route' => ['FOUNDATION', 'international'],
                'international_places_open' => [null, 'international'],
            ];
            foreach ($map as $field => [$qual, $applies]) {
                $v = $row[$field] ?? null;
                if (! $v) {
                    continue;
                }
                $st = $status;
                if (preg_match('/NOT PUBLISHED|does not publish|No Nigeria/i', $v)) {
                    $st = ReferenceFact::NOT_PUBLISHED;
                }
                if (preg_match('/DATA UNAVAILABLE|NOT FOUND|not captured/i', $v) && $field !== 'waec_neco_statement') {
                    $st = ReferenceFact::NOT_FOUND;
                }
                $this->fact($u, $field, $v, $src, $st, 'official', ['qualification_code' => $qual, 'applies_to' => $applies, 'academic_year' => $cycle,
                    'notes' => $field === 'waec_neco_statement' && isset($row['waec_statement_is_medicine_specific']) ? ($row['waec_statement_is_medicine_specific'] ? 'Medicine-specific statement.' : 'General university statement; Medicine applicability must be confirmed.') : null]);
            }
            foreach (array_slice($row['sources'] ?? [], 0, 6) as $i => $url) {
                $this->fact($u, 'source_'.($i + 1), $url, $url, ReferenceFact::VERIFY_ON_PAGE, 'official', ['applies_to' => 'all']);
            }
        }
    }

    private function awardFrom(string $title): ?string
    {
        foreach (['MB BChir', 'MB ChB', 'MBChB', 'MB BCh', 'MBBCh', 'BM BCh', 'BMBS', 'MBBS', 'BM BS', 'MB BS'] as $a) {
            if (stripos($title, $a) !== false) {
                return str_replace(' ', '', $a) === 'MBChB' ? 'MBChB' : $a;
            }
        }

        return null;
    }

    private function routeFrom($v): ?string
    {
        if (! is_string($v)) {
            return null;
        }
        $u = Str::upper($v);
        if (str_contains($u, 'UCAS') && (str_contains($u, 'DIRECT') || str_contains($u, 'OR'))) {
            return 'BOTH';
        }
        if (str_contains($u, 'UCAS')) {
            return 'UCAS';
        }
        if (str_contains($u, 'DIRECT')) {
            return 'DIRECT';
        }

        return null;
    }

    private function testFrom($v): ?string
    {
        if (! is_string($v)) {
            return null;
        }
        $u = Str::upper($v);
        if (str_contains($u, 'GAMSAT') && str_contains($u, 'UCAT')) {
            return 'UCAT/GAMSAT';
        }
        if (str_contains($u, 'UCAT')) {
            return 'UCAT';
        }
        if (str_contains($u, 'GAMSAT')) {
            return 'GAMSAT';
        }
        if (str_contains($u, 'NONE') || str_contains($u, 'NO ')) {
            return 'NONE';
        }

        return 'NOT_PUBLISHED';
    }
}
