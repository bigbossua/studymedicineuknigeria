<?php

namespace App\Models;

use App\Support\FactAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReferenceFact extends Model
{
    public const VERIFIED = 'VERIFIED';

    public const VERIFY_ON_PAGE = 'VERIFY-ON-PAGE';

    public const NOT_PUBLISHED = 'NOT_PUBLISHED';

    public const NOT_FOUND = 'NOT_FOUND';

    public const REVIEW_DUE = 'REVIEW_DUE';

    public const SOURCE_CHANGED = 'SOURCE_CHANGED';

    public const ARCHIVED = 'ARCHIVED';

    protected $guarded = [];

    protected $casts = [
        'value_number' => 'float',
        'value_bool' => 'boolean',
        'value_json' => 'array',
        'verified_at' => 'date',
        'review_due_at' => 'date',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(fn (ReferenceFact $fact) => FactAudit::record($fact));
    }

    /** Audit trail, newest first (fact_changes). */
    public function history(): HasMany
    {
        return $this->hasMany(FactChange::class)->latest('id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Whether this fact may be shown to the public in the current environment. */
    public function isPublishable(): bool
    {
        if ($this->verification_status === self::VERIFIED) {
            return true;
        }

        return self::showsUnverified();
    }

    /**
     * Whether facts that are not VERIFIED may be shown: in local development and tests, or where SITE_PUBLISH_UNVERIFIED
     * is set on purpose. Staging behaves like production, so the owner reviews exactly what would go live.
     */
    public static function showsUnverified(): bool
    {
        return (bool) config('site.publish_unverified') || app()->environment('local', 'testing');
    }

    public function displayValue(): ?string
    {
        if ($this->value_text !== null) {
            return $this->value_text;
        }
        if ($this->value_number !== null) {
            return str_ends_with($this->key, '_gbp')
                ? '£'.number_format((float) $this->value_number, 0)
                : rtrim(rtrim(number_format((float) $this->value_number, 2, '.', ','), '0'), '.');
        }
        if ($this->value_bool !== null) {
            return $this->value_bool ? 'Yes' : 'No';
        }
        if ($this->value_json !== null) {
            return json_encode($this->value_json);
        }

        return null;
    }
}
