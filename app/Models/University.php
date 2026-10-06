<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class University extends Model
{
    protected $guarded = [];

    protected $casts = ['msc_member' => 'boolean', 'published' => 'boolean'];

    /**
     * Universities with a Medicine course. The public directory, the school pages and every school count read
     * universities through this scope: `international_policy` and the university-level statements describe Medicine,
     * so a provider recorded only for another subject (nursing, pharmacy…) must never appear as a medical school.
     */
    public function scopeMedicalSchools(Builder $query): Builder
    {
        // A record with no courses yet (the importer creates one from a statement row) is still a medical school record;
        // only a university whose courses are all for other subjects is excluded.
        return $query->where(fn ($q) => $q->whereHas('courses', fn ($c) => $c->medicine())->orWhereDoesntHave('courses'));
    }

    public function isMedicalSchool(): bool
    {
        return $this->courses()->medicine()->exists() || ! $this->courses()->exists();
    }

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

    /** GMC status wording for public pages: shown only while the matching fact is publishable (verified, or outside production). */
    public function publicGmcStatus(): ?string
    {
        if (! $this->gmc_status) {
            return null;
        }
        $fact = $this->fact('gmc_status');

        return ($fact ? $fact->isPublishable() : ReferenceFact::showsUnverified()) ? $this->gmc_status : null;
    }

    public function primaryCourse(): ?Course
    {
        return $this->courses->firstWhere('entry_type', 'standard') ?? $this->courses->first();
    }

    /** UK universities, regulators and admissions bodies only: a pathway agent's or a prep company's page is never "official". */
    public static function isOfficialUrl(?string $url): bool
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return $host !== '' && (bool) preg_match('/(^|\.)(ac\.uk|nhs\.uk|gov\.uk|gmc-uk\.org|ucas\.com|legislation\.gov\.uk)$/', $host);
    }

    /** The university's own pages behind its facts, for the "Official sources" box and the structured data. */
    public function officialUrls(): Collection
    {
        return $this->facts->filter(fn ($f) => str_starts_with($f->key, 'source_'))->pluck('value_text')
            ->merge($this->facts->pluck('source_url'))
            ->merge($this->courses->pluck('official_url'))
            ->merge([$this->website_url])
            ->filter(fn ($u) => self::isOfficialUrl($u))->unique()->values();
    }
}
