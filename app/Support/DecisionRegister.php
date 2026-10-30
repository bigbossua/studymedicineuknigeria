<?php

namespace App\Support;

/**
 * Reads data/seo/decision-register.csv (edited in the repository, deployed with the code). A path is approved for the
 * index only when a row with a live status (PUBLISHED, INDEXING, MEASURING, UPDATE) names it as its current_url: one
 * page per intent, with the demand evidence and decision recorded in that row (docs/seo/DECISION-ENGINE.md §8.3).
 * Verified facts alone never approve a page: since 2026-10-29 a school published in Admin stays noindex until its
 * register row is live, so factual verification, demand evidence and publication approval stay separate decisions.
 * The one other approval is an owner-approved exception: a RESEARCH row whose indexation_decision starts with
 * OWNER_EXCEPTION (written by ops/seo/build-register.py for an explicit owner decision; Cambridge, King's College London
 * and Queen's University Belfast on 2026-10-30). It records no demand evidence; SchoolRegisterGateTest pins the list.
 */
final class DecisionRegister
{
    public const LIVE = ['PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE'];

    public const OWNER_EXCEPTION = 'index: owner-approved exception';

    /** @var array<string, array{id: string, status: string, indexation_decision: string}>|null path => row, cached per process */
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

    /** A RESEARCH row the owner explicitly approved for the index without demand evidence (see the class comment). */
    public static function isOwnerException(string $path): bool
    {
        $row = self::rowFor($path);

        return $row !== null && $row['status'] === 'RESEARCH' && str_starts_with($row['indexation_decision'], self::OWNER_EXCEPTION);
    }

    /** May this path be indexed as far as the register is concerned: a live row, or an owner-approved exception. */
    public static function approves(string $path): bool
    {
        return self::isLive($path) || self::isOwnerException($path);
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
                    self::$rows['/'.trim($path, '/')] = ['id' => $row['id'], 'status' => $row['status'], 'indexation_decision' => $row['indexation_decision'] ?? ''];
                }
            }
        }
        fclose($h);

        return self::$rows;
    }
}
