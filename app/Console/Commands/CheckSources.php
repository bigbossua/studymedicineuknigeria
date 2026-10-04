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
 *  - page changed: a verified fact whose wording or figure is still on the page stays verified (noted); one whose value
 *    is no longer found becomes SOURCE_CHANGED and goes back into the review queue;
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
                $res = Http::timeout(20)->withHeaders(['User-Agent' => 'StudyMedicineUKNigeria-source-check/1.0 (+'.config('app.url').'/how-we-verify)'])->get($url);
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
            $hash = hash('sha256', $text);
            if (! $prev || ! $prev->content_hash) {
                $this->snapshot($url, $hash, $status, null);
                $baselined++;

                continue;
            }
            if ($prev->content_hash === $hash) {
                $this->snapshot($url, $hash, $status, null);

                continue;
            }
            // The page changed: check each verified value against the new text.
            foreach (ReferenceFact::where('verification_status', ReferenceFact::VERIFIED)->where('source_url', $url)->get() as $fact) {
                if (self::valueIsOnPage($fact, $text)) {
                    $kept++;
                    if (! $this->option('dry-run')) {
                        $fact->notes = trim(($fact->notes ? $fact->notes.' ' : '').'[Source changed '.now()->toDateString().'; verified value still on the page]');
                        $fact->saveQuietly();
                    }
                } else {
                    $flagged += $this->flagOne($fact, 'Official page changed on '.now()->toDateString().' and the verified value was no longer found on it.', $report);
                }
            }
            $this->snapshot($url, $hash, $status, null, true);
        }
        $summary = ($this->option('dry-run') ? '[dry run] ' : '')."{$urls->count()} page(s) checked: {$flagged} fact(s) un-verified (source changed), {$kept} still on a changed page, {$baselined} first fingerprint(s), {$errors} unreachable.";
        $this->info($summary);
        if ($flagged && ! $this->option('dry-run')) {
            Log::warning('sources.check', ['flagged' => $flagged, 'kept' => $kept, 'errors' => $errors]);
            User::where('role', 'admin')->get()->each->notify(new StaffNotification("{$flagged} verified fact(s) need re-verification", array_merge([$summary], array_slice($report, 0, 20)), route('admin.reference.sources')));
        }

        return self::SUCCESS;
    }

    /** Visible text only, entities decoded, whitespace collapsed, lower case. */
    public static function normalise(string $html): string
    {
        $html = preg_replace('#<(script|style|noscript|svg|template)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags(str_replace(['<br', '</p', '</li', '</td', '</div'], [' <br', ' </p', ' </li', ' </td', ' </div'], $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(mb_strtolower(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)) ?? $text));
    }

    public static function valueIsOnPage(ReferenceFact $fact, string $text): bool
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

        return $value !== '' && str_contains($text, $value);
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
