<?php

namespace App\Console\Commands;

use App\Models\ReferenceFact;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Support\FactAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Watches the official pages behind VERIFIED facts so an old verified value never outlives a material change to its
 * source. For each page: fetch, reduce to normalised text, compare with the last fingerprint.
 *  - first visit: record the fingerprint (no change);
 *  - page gone (404/410): every verified fact on it becomes SOURCE_CHANGED (hidden in production);
 *  - page changed: a verified fact whose wording or figure is still on the page stays verified (noted), and so does one
 *    whose recorded official quote is still published word for word (a summarised value never appears verbatim); one
 *    for which neither is found becomes SOURCE_CHANGED and goes back into the review queue;
 *  - address now redirects to a different page (e.g. a 2026-entry course page forwarded to the 2027 one): every run,
 *    first visit included, each verified value is checked against the page it lands on, even when that page has not
 *    changed since it was fingerprinted (the redirect may predate the fingerprint, as with Cardiff found 2026-10-30);
 *  - network error or 5xx: nothing changes (recorded; retried next run).
 * Staff are emailed a summary when anything changes. Runs nightly on the server (routes/console.php); this build
 * environment cannot reach official domains.
 */
class CheckSources extends Command
{
    protected $signature = 'smukn:sources-check {--dry-run : report without changing any fact} {--limit=250 : most pages to fetch in one run}';

    protected $description = 'Detect changes on the official pages behind verified facts and un-verify facts whose value is no longer published';

    public function handle(): int
    {
        return FactAudit::using('source watcher', fn () => $this->check());
    }

    private function check(): int
    {
        $urls = ReferenceFact::where('verification_status', ReferenceFact::VERIFIED)->whereNotNull('source_url')->where('source_url', 'like', 'https://%')
            ->distinct()->orderBy('source_url')->limit((int) $this->option('limit'))->pluck('source_url');
        $flagged = $kept = $errors = $baselined = 0;
        $report = [];
        foreach ($urls as $url) {
            try {
                $res = Http::timeout(20)->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                    ->withHeaders(['User-Agent' => 'StudyMedicineUKNigeria-source-check/1.0 (+'.config('app.url').'/how-we-verify)'])->get($url);
                $status = $res->status();
                $text = $res->successful() ? self::normalise($res->body()) : null;
            } catch (Throwable $e) {
                $this->snapshot($url, null, null, substr($e->getMessage(), 0, 250));
                $errors++;

                continue;
            }
            $prev = DB::table('source_snapshots')->where('url_hash', hash('sha256', $url))->first();
            if (in_array($status, [404, 410], true)) {
                $flagged += $this->flag($url, "Official page returned {$status} on ".now()->toDateString().'.', $report);
                $this->snapshot($url, null, $status, null, true);

                continue;
            }
            if ($text === null) {
                $this->snapshot($url, $prev?->content_hash, $status, "HTTP {$status}");
                $errors++;

                continue;
            }
            if (self::isChallenge($res->body(), $text)) {
                // A bot check answered instead of the page (often with 200): nothing about the page is known, so no
                // fact is judged against it. On 2026-10-29 Buckingham served "Bot Verification" and two facts were flagged.
                $this->snapshot($url, $prev?->content_hash, $status, 'bot challenge instead of the page');
                $errors++;

                continue;
            }
            $hash = hash('sha256', $text);
            $movedTo = self::redirectedTo((string) $res->header('X-Guzzle-Redirect-History'), $url);
            $changed = $prev && $prev->content_hash && $prev->content_hash !== $hash;
            if (! $prev || ! $prev->content_hash) {
                $this->snapshot($url, $hash, $status, null);
                $baselined++;
                if ($movedTo === null) {
                    continue;
                }
            } elseif (! $changed && $movedTo === null) {
                $this->snapshot($url, $hash, $status, null);

                continue;
            }
            // The page changed, or the address forwards to another page: check each verified value against the text served.
            foreach (ReferenceFact::where('verification_status', ReferenceFact::VERIFIED)->where('source_url', $url)->get() as $fact) {
                $match = self::match($fact, $text);
                if ($match === 'value' || $match === 'quote') {
                    $kept++;
                    if ($changed && ! $this->option('dry-run')) {
                        $fact->notes = trim(($fact->notes ? $fact->notes.' ' : '').'[Source changed '.now()->toDateString().'; '.($match === 'value' ? 'verified value still on the page' : 'the official wording it was verified against is still on the page word for word').']');
                        $fact->saveQuietly();
                    }
                } else {
                    $flagged += $this->flagOne($fact, ($changed ? 'Official page changed on ' : 'Official address now redirects to '.$movedTo.'; checked on ').now()->toDateString().($match === 'partial'
                        ? '; part of the official wording this fact was verified against is gone. Manual review: confirm or correct the value on the current page.'
                        : ' and neither the verified value nor its recorded official wording was found on it.'), $report);
                }
            }
            $this->snapshot($url, $hash, $status, null, $changed);
        }
        $summary = ($this->option('dry-run') ? '[dry run] ' : '')."{$urls->count()} page(s) checked: {$flagged} fact(s) un-verified (source changed), {$kept} still on a changed page, {$baselined} first fingerprint(s), {$errors} unreachable.";
        $this->info($summary);
        if ($flagged && ! $this->option('dry-run')) {
            Log::warning('sources.check', ['flagged' => $flagged, 'kept' => $kept, 'errors' => $errors]);
            User::where('role', 'admin')->get()->each->notify(new StaffNotification("{$flagged} verified fact(s) need re-verification", array_merge([$summary], array_slice($report, 0, 20)), route('admin.reference.sources')));
        }

        return self::SUCCESS;
    }

    /**
     * The address a request ended at after redirects, when it is a different page from the one recorded (scheme, case,
     * "www.", a trailing slash and the query string do not count). Null when the page was served at its own address.
     */
    public static function redirectedTo(string $history, string $url): ?string
    {
        $last = trim((string) collect(explode(',', $history))->filter(fn ($u) => trim($u) !== '')->last());
        $key = fn (string $u) => preg_replace('/^www\./', '', mb_strtolower(rtrim((parse_url($u, PHP_URL_HOST) ?? '').(parse_url($u, PHP_URL_PATH) ?? ''), '/')));

        return $last !== '' && $key($last) !== $key($url) ? $last : null;
    }

    /** A bot or browser check (Cloudflare, captcha, "Bot Verification") served in place of the official page. */
    public static function isChallenge(string $html, string $text): bool
    {
        $title = preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m) ? mb_strtolower(trim(html_entity_decode(strip_tags($m[1])))) : '';
        if (preg_match('/just a moment|bot verification|human verification|checking your browser|attention required|verify you are (a )?human|security check|access denied|are you a robot/u', $title)) {
            return true;
        }

        return mb_strlen($text) < 1500 && preg_match('/cf-chl|challenge-platform|_cf_chl_opt|cf-turnstile|h-captcha|hcaptcha|g-recaptcha|captcha-delivery|px-captcha|bot verification|human verification/i', $html) === 1;
    }

    /** Visible text only, entities decoded, whitespace collapsed, lower case. */
    public static function normalise(string $html): string
    {
        $html = preg_replace('#<(script|style|noscript|svg|template)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags(str_replace(['<br', '</p', '</li', '</td', '</div'], [' <br', ' </p', ' </li', ' </td', ' </div'], $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(mb_strtolower(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)) ?? $text));
    }

    /** 'value' (the value itself is on the page), 'quote' (all of its recorded official wording is), 'partial' (only some of that wording is: manual review) or 'none'. */
    public static function match(ReferenceFact $fact, string $text): string
    {
        if (self::valueIsOnPage($fact, $text, false)) {
            return 'value';
        }
        $parts = self::quoteParts((string) $fact->notes);
        $found = count(array_filter($parts, fn ($p) => str_contains($text, $p)));

        return $parts === [] || $found === 0 ? 'none' : ($found === count($parts) ? 'quote' : 'partial');
    }

    public static function valueIsOnPage(ReferenceFact $fact, string $text, bool $orQuote = true): bool
    {
        if ($fact->value_number !== null && $fact->value_text === null) {
            $n = (float) $fact->value_number;
            $variants = [number_format($n, 0, '.', ','), number_format($n, 0, '.', ''), number_format($n, 2, '.', ','), number_format($n, 0, '.', ' ')];
            foreach ($variants as $v) {
                if (preg_match('/(?<![\d,.])'.preg_quote($v, '/').'(?![\d])/u', $text)) {
                    return true;
                }
            }

            return false;
        }
        $value = trim(mb_strtolower(preg_replace('/\s+/u', ' ', (string) $fact->value_text) ?? ''));
        if ($value !== '' && str_contains($text, $value)) {
            return true;
        }

        // A text value is often the reviewer's summary of the page, never its exact wording; the official wording it was
        // verified against is kept in the notes as quote: "…". The fact stays verified only while every part of that
        // quote is still published (parts are split at the line breaks ⏎ and elisions ... of the original excerpt).
        return $orQuote && self::quoteIsOnPage((string) $fact->notes, $text);
    }

    public static function quoteIsOnPage(string $notes, string $text): bool
    {
        $parts = self::quoteParts($notes);

        return $parts !== [] && count(array_filter($parts, fn ($p) => str_contains($text, $p))) === count($parts);
    }

    /** @return list<string> the recorded official quote, normalised and split into parts of 12+ characters */
    private static function quoteParts(string $notes): array
    {
        // the last review's quote; a long one was cut short when stored, so it may end at "]" without its closing quote mark
        if (($at = mb_strrpos($notes, 'quote: "')) === false) {
            return [];
        }
        $quote = mb_substr($notes, $at + 8);
        $quote = mb_substr($quote, 0, ($end = mb_strpos($quote, '"]')) !== false ? $end : (mb_strpos($quote, ']') !== false ? mb_strpos($quote, ']') : null));

        // a reviewer's [bracketed remark] inside the quote is not page text
        $quote = preg_replace('/\[[^\]]*(?:\]|$)/su', ' ', $quote) ?? $quote;

        return array_values(array_filter(array_map(fn ($p) => trim(mb_strtolower(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $p)) ?? '')),
            preg_split('/\s*(?:⏎|\.\.\.|…)\s*/u', html_entity_decode($quote, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: []), fn ($p) => mb_strlen($p) >= 12));
    }

    private function flag(string $url, string $note, array &$report): int
    {
        $n = 0;
        foreach (ReferenceFact::where('verification_status', ReferenceFact::VERIFIED)->where('source_url', $url)->get() as $fact) {
            $n += $this->flagOne($fact, $note, $report);
        }

        return $n;
    }

    private function flagOne(ReferenceFact $fact, string $note, array &$report): int
    {
        $report[] = "{$fact->key} — {$fact->source_url}";
        if (! $this->option('dry-run')) {
            $fact->verification_status = ReferenceFact::SOURCE_CHANGED;
            $fact->notes = trim(($fact->notes ? $fact->notes.' ' : '')."[{$note}]");
            $fact->save();
        }

        return 1;
    }

    private function snapshot(string $url, ?string $hash, ?int $status, ?string $error, bool $changed = false): void
    {
        if ($this->option('dry-run')) {
            return;
        }
        $row = ['url' => $url, 'status_code' => $status, 'fetched_at' => now(), 'last_error' => $error, 'updated_at' => now()];
        if ($hash !== null || $changed) {
            $row['content_hash'] = $hash;
        }
        if ($changed) {
            $row['changed_at'] = now();
        }
        DB::table('source_snapshots')->updateOrInsert(['url_hash' => hash('sha256', $url)], $row + ['created_at' => now()]);
    }
}
