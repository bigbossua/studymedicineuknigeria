<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\ReferenceFact;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Evidence pre-check for the verification queue: fetches each official page behind a pending fact once and reports
 * whether the recorded value appears on it, with a short quotation. It never changes a fact. A person still opens the
 * page and verifies in Admin → Verification (the published policy: a member of the team reads the value on the page);
 * this report only puts the likely confirmations, the mismatches and the dead links in front of them first.
 *
 * Match levels: exact (the value's wording or figure is on the page) · partial (most of the value's distinctive
 * words are) · none (nothing found: likely changed or not on this page) · fetch_failed (page unreachable or not HTML).
 */
class FactsEvidence extends Command
{
    protected $signature = 'smukn:facts-evidence {file : CSV to write} {--max-priority=7 : Highest priority tier to include (1 = UCAS deadlines … 7 = visa)} {--status=VERIFY-ON-PAGE,SOURCE_CHANGED,REVIEW_DUE}';

    protected $description = 'Report whether each pending fact\'s value appears on its official page (read-only; never verifies)';

    public const HEADER = ['ref', 'priority', 'priority_area', 'subject', 'key', 'current_value', 'source_url', 'http_status', 'match', 'evidence', 'checked_at'];

    public function handle(): int
    {
        $statuses = array_filter(array_map('trim', explode(',', (string) $this->option('status'))));
        $max = (int) $this->option('max-priority');
        $facts = ReferenceFact::with(['subject' => fn ($m) => $m->morphWith([Course::class => ['university']])])
            ->whereIn('verification_status', $statuses)->get()
            ->filter(fn ($f) => ExportFactsWorksheet::priority($f) <= $max)
            ->sortBy(fn ($f) => [ExportFactsWorksheet::priority($f), $f->source_url ?? 'zzz', $f->key])->values();

        $pages = [];
        $out = fopen($this->argument('file'), 'w');
        fputcsv($out, self::HEADER, ',', '"', '');
        $tally = [];
        foreach ($facts as $f) {
            $url = (string) $f->source_url;
            if (! isset($pages[$url])) {
                $pages[$url] = $this->fetch($url);
            }
            [$status, $text] = $pages[$url];
            [$match, $evidence] = $text === null ? ['fetch_failed', ''] : self::find($f, $text);
            $tally[$match] = ($tally[$match] ?? 0) + 1;
            [$tier, $area] = ExportFactsWorksheet::tier($f);
            $subject = $f->subject instanceof Course ? trim(($f->subject->university?->name ?? '').' · '.$f->subject->title) : ($f->subject->name ?? $f->subject->title ?? '');
            fputcsv($out, array_map([ExportFactsWorksheet::class, 'cell'], [ExportFactsWorksheet::ref($f), $tier, $area, $subject, $f->key,
                mb_substr((string) $f->displayValue(), 0, 300), $url, $status, $match, $evidence, now()->toDateString()]), ',', '"', '');
        }
        fclose($out);
        ksort($tally);
        $this->info($facts->count().' fact(s) on '.count($pages).' page(s): '.collect($tally)->map(fn ($n, $k) => "$k $n")->implode(', ').'. Nothing was changed; verify in Admin → Verification.');

        return self::SUCCESS;
    }

    /** @return array{0: int|string, 1: ?string} HTTP status (or error) and the page's visible text */
    private function fetch(string $url): array
    {
        if (! str_starts_with($url, 'https://')) {
            return ['no https source', null];
        }
        try {
            $r = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; StudyMedicineUKNigeria-fact-check/1.0; +https://studymedicineuknigeria.com/how-we-verify)', 'Accept-Language' => 'en-GB'])
                ->timeout(25)->retry(1, 2000, throw: false)->get($url);
            if (! $r->successful() || ! str_contains((string) $r->header('Content-Type'), 'html')) {
                return [$r->status().(str_contains((string) $r->header('Content-Type'), 'pdf') ? ' pdf' : ''), null];
            }

            return [$r->status(), self::text($r->body())];
        } catch (Throwable $e) {
            return ['error: '.class_basename($e), null];
        }
    }

    public static function text(string $html): string
    {
        $html = preg_replace('#<(script|style|noscript|svg)\b[^>]*>.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags(preg_replace('#<(br|/p|/li|/h\d|/td|/tr|/div)[^>]*>#i', ' $0 ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)));
    }

    /** @return array{0: string, 1: string} match level and a quotation of up to 200 characters */
    public static function find(ReferenceFact $f, string $text): array
    {
        $lower = mb_strtolower($text);
        $quote = function (int $pos, int $len) use ($text) {
            $start = max(0, $pos - 80);

            return '…'.trim(mb_substr($text, $start, $len + 160)).'…';
        };
        // figures inside the wording (dates, £ amounts, times, larger numbers): all must be on the page
        $figures = self::figures((string) $f->value_text);
        if ($figures) {
            $found = array_filter($figures, fn ($x) => mb_stripos($text, $x) !== false || mb_stripos($text, str_replace(',', '', $x)) !== false);
            if (count($found) === count($figures)) {
                $p = mb_stripos($text, reset($figures)) ?: mb_stripos($text, str_replace(',', '', reset($figures)));

                return ['exact', mb_substr($quote((int) $p, 30), 0, 200)];
            }
            if ($found) {
                $p = mb_stripos($text, reset($found));

                return ['partial', mb_substr('missing '.implode(', ', array_diff($figures, $found)).' · '.$quote((int) $p, 30), 0, 200)];
            }
        }
        foreach (self::needles($f) as $needle) {
            $p = mb_stripos($text, $needle);
            if ($needle !== '' && $p !== false) {
                return ['exact', mb_substr($quote($p, mb_strlen($needle)), 0, 200)];
            }
        }
        // free text: share of its distinctive words on the page
        $value = mb_strtolower((string) $f->value_text);
        $words = array_values(array_unique(array_filter(preg_split('/[^\p{L}\p{N}£%]+/u', $value), fn ($w) => mb_strlen($w) >= 5)));
        if (count($words) >= 4) {
            $hits = array_filter($words, fn ($w) => str_contains($lower, $w));
            if (count($hits) / count($words) >= 0.7) {
                $first = mb_stripos($text, reset($hits));

                return ['partial', mb_substr($quote((int) $first, 40), 0, 200)];
            }
        }

        return ['none', ''];
    }

    /** Forms the value can take on an official page: figures with and without separators, dates, exact wording. */
    public static function needles(ReferenceFact $f): array
    {
        $n = [];
        if ($f->value_number !== null) {
            $v = (float) $f->value_number;
            $int = fmod($v, 1.0) === 0.0;
            $n[] = $int ? number_format($v, 0, '.', ',') : number_format($v, 2, '.', ',');
            $n[] = $int ? number_format($v, 0, '.', '') : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        }
        if ($f->value_date ?? null) {
            $d = Carbon::parse($f->value_date);
            array_push($n, $d->format('j F Y'), $d->format('jS F Y'), $d->format('j M Y'), $d->format('F j, Y'), $d->format('j F'));
        }
        $t = trim((string) $f->value_text);
        if ($t !== '' && mb_strlen($t) <= 60) {
            $n[] = $t;
        } elseif ($t !== '') {
            $n[] = mb_substr($t, 0, 60); // the opening of a quoted statement
        }

        return array_values(array_unique(array_filter($n, fn ($x) => mb_strlen((string) $x) >= 3)));
    }

    /** Dates ("15 October 2026", "15 October"), money ("£49,700"), times ("18:00") and numbers of 3+ digits. */
    public static function figures(string $t): array
    {
        $months = 'January|February|March|April|May|June|July|August|September|October|November|December';
        preg_match_all("/\\b\\d{1,2}(?:st|nd|rd|th)? (?:$months)(?: \\d{4})?|£\\s?\\d[\\d,]*(?:\\.\\d+)?|\\b\\d{1,2}:\\d{2}\\b|\\b\\d{1,3}(?:,\\d{3})+\\b|\\b\\d{3,}\\b/u", $t, $m);

        return array_values(array_unique(array_map(fn ($x) => preg_replace('/^£\\s+/', '£', trim($x)), $m[0])));
    }
}
