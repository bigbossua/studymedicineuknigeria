<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class University extends Model
{
    protected $guarded = [];

    protected $casts = ['msc_member' => 'boolean', 'published' => 'boolean'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function facts(): MorphMany
    {
        return $this->morphMany(ReferenceFact::class, 'subject');
    }

    public function fact(string $key): ?ReferenceFact
    {
        return $this->facts->firstWhere('key', $key);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function primaryCourse(): ?Course
    {
        return $this->courses->firstWhere('entry_type', 'standard') ?? $this->courses->first();
    }
}
