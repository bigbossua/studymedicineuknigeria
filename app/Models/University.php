<?php

namespace App\Models;

use App\Support\DecisionRegister;
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

    /** DECISION-ENGINE §8.1: the five facts a school page needs before it may be indexed (university-level, then course-level keys). */
    public const CORE_FACTS = ['international_accepted', 'waec_neco_statement', 'english_requirement', 'international_fee_gbp', 'application_route'];

    /**
     * Indexable = published by staff (or the publication list) AND approved by the decision register (a live row:
     * §8.3 Nigerian demand evidence and the publication decision, or a recorded owner-approved exception) AND every
     * §8.1 core fact currently VERIFIED, or recorded as NOT_PUBLISHED by the university. A core fact taken off the
     * site (source changed, review due, never verified) puts the page back to noindex and out of the sitemap until it
     * is re-verified, as the topic pages' PublishGate does; verified facts alone never index a page the register has
     * not approved. The page itself stays online and nothing is deleted.
     */
    public function isIndexable(): bool
    {
        return $this->indexBlocker() === null;
    }

    /** Why the page is noindex (for Admin), or null when it is indexable. */
    public function indexBlocker(): ?string
    {
        if (! $this->published) {
            return 'not published';
        }
        if (! DecisionRegister::approves('/medical-schools/'.$this->slug)) {
            $row = DecisionRegister::rowFor('/medical-schools/'.$this->slug);

            return 'no live decision-register row'.($row ? " ({$row['id']} {$row['status']})" : '').': demand evidence and publication approval not recorded';
        }
        $statuses = ReferenceFact::where(fn ($q) => $q->where(fn ($a) => $a->where('subject_type', self::class)->where('subject_id', $this->id))
            ->orWhere(fn ($b) => $b->where('subject_type', Course::class)->whereIn('subject_id', $this->courses()->select('id'))))
            ->whereIn('key', self::CORE_FACTS)->whereIn('verification_status', [ReferenceFact::VERIFIED, ReferenceFact::NOT_PUBLISHED])
            ->distinct()->pluck('key');
        $missing = array_diff(self::CORE_FACTS, $statuses->all());

        return $missing ? 'core facts not verified: '.implode(', ', $missing) : null;
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
