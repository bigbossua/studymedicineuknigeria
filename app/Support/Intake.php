<?php

namespace App\Support;

/**
 * The entry year to pre-select on forms. From September the UCAT window for next year's entry has passed, so the
 * realistic default moves on a year. A default only: the student chooses, and no deadline is stated from this.
 */
final class Intake
{
    public static function defaultYear(): int
    {
        return now()->month >= 9 ? now()->year + 2 : now()->year + 1;
    }
}
