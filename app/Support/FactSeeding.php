<?php

namespace App\Support;

use App\Models\ReferenceFact;
use Illuminate\Database\Eloquent\Model;

/**
 * The one rule every seeder and dataset import follows: create a fact that does not exist; refresh a fact nobody has
 * reviewed; never touch a fact a person has decided on (reviewed_at). Re-running seeders (each deploy runs
 * smukn:reference-sync) therefore cannot overwrite a verified value or reverse a reviewer's decision.
 */
final class FactSeeding
{
    /**
     * @param  array<string, mixed>  $match  identifying columns (key, academic_year, qualification_code…)
     * @param  array<string, mixed>  $attrs  values, source, notes
     * @return 'created'|'updated'|'kept'
     */
    public static function upsert(Model $subject, array $match, array $attrs, string $status): string
    {
        /** @var ReferenceFact|null $existing */
        $existing = $subject->facts()->where($match)->first();
        if (! $existing) {
            $subject->facts()->create($attrs + $match + ['verification_status' => $status]);

            return 'created';
        }
        if ($existing->reviewed_at !== null || $existing->verification_status === ReferenceFact::VERIFIED) {
            return 'kept';
        }
        FactAudit::using('reference sync', fn () => $existing->update($attrs + ['verification_status' => $status]));

        return 'updated';
    }
}
