<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** A non-university subject for reference facts (a cycle, a test, a visa rule set). */
class Topic extends Model
{
    protected $guarded = [];

    public function facts(): MorphMany { return $this->morphMany(ReferenceFact::class, 'subject'); }

    public function fact(string $key): ?ReferenceFact { return $this->facts->firstWhere('key', $key); }

    public static function bySlug(string $slug): ?self { return static::with('facts')->firstWhere('slug', $slug); }
}
