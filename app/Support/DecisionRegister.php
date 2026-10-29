<?php

namespace App\Support;

/**
 * Reads data/seo/decision-register.csv (edited in the repository, deployed with the code). A path is approved for the
 * index only when a row with a live status (PUBLISHED, INDEXING, MEASURING, UPDATE) names it as its current_url: one
 * page per intent, with the demand evidence and decision recorded in that row (docs/seo/DECISION-ENGINE.md §8.3).
 * Verified facts alone never approve a page: since 2026-10-29 a school published in Admin stays noindex until its
 * register row is live, so factual verification, demand evidence and publication approval stay separate decisions.
 */
final class DecisionRegister
{
    public const LIVE = ['PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE'];

    /** @var array<string, array{id: string, status: string}>|null path => row, cached per process */
    private static ?array $rows = null;

    /** The register row whose current_url is this path, or null. */
    public static function rowFor(string $path): ?array
    {
        return self::rows()['/'.trim($path, '/')] ?? null;
    }

    public static function isLive(string $path): bool
    {
        $row = self::rowFor($path);

        return $row !== null && in_array($row['status'], self::LIVE, true);
    }

    /** Clears the cache (tests that rewrite the register). */
    public static function flush(): void
    {
        self::$rows = null;
    }

    private static function rows(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }
        self::$rows = [];
        if (($h = @fopen(base_path('data/seo/decision-register.csv'), 'r')) === false) {
            return self::$rows; // no register: nothing is approved, so nothing gated by it is indexed
        }
        $head = fgetcsv($h, null, ',', '"', '');
        while (($r = fgetcsv($h, null, ',', '"', '')) !== false) {
            if (count($r) !== count($head)) {
                continue;
            }
            $row = array_combine($head, $r);
            $path = strtok(trim($row['current_url'] ?? ''), '#');
            if ($path && str_starts_with($path, '/')) {
                // a path named by several rows keeps its live row: one live row is enough to approve it
                $existing = self::$rows['/'.trim($path, '/')] ?? null;
                if ($existing === null || ! in_array($existing['status'], self::LIVE, true)) {
                    self::$rows['/'.trim($path, '/')] = ['id' => $row['id'], 'status' => $row['status']];
                }
            }
        }
        fclose($h);

        return self::$rows;
    }
}
