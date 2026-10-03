<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Course extends Model
{
    protected $guarded = [];

    protected $casts = ['published' => 'boolean'];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function facts(): MorphMany
    {
        return $this->morphMany(ReferenceFact::class, 'subject');
    }

    public function fact(string $key): ?ReferenceFact
    {
        return $this->facts->firstWhere('key', $key);
    }

    /** The bare UCAS course code (e.g. A100); the imported field sometimes carries research notes that belong in facts, not in public labels. */
    public function shortUcasCode(): ?string
    {
        if (! $this->ucas_code) {
            return null;
        }

        return preg_match('/\b([A-Z]\d{3})\b/', $this->ucas_code, $m) ? $m[1] : null;
    }

    /** Latest international fee fact (by academic year string, descending). */
    public function internationalFee(): ?ReferenceFact
    {
        return $this->facts->where('key', 'international_fee_gbp')->sortByDesc('academic_year')->first();
    }
}
