<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One UK healthcare profession or course subject in the master taxonomy (data/healthcare/subjects.json). */
class Profession extends Model
{
    public const STATUSES = ['RESEARCH', 'VALIDATED', 'BUILD', 'DRAFT', 'REVIEW', 'PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE', 'REJECTED'];

    /** Statuses under which a public, indexable page for the subject may exist. */
    public const LIVE = ['PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE'];

    protected $guarded = [];

    protected $casts = ['flagship' => 'boolean', 'alternative_names' => 'array', 'sources' => 'array', 'decision_register_ids' => 'array', 'last_researched' => 'date'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'profession', 'slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function mayHavePublicPage(): bool
    {
        return in_array($this->status, self::LIVE, true);
    }
}
