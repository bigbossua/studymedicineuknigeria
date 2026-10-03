<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTier extends Model
{
    protected $guarded = [];

    protected $casts = ['deliverables' => 'array', 'exclusions' => 'array', 'active' => 'boolean'];

    public function prices(): HasMany
    {
        return $this->hasMany(TierPrice::class)->where('active', true);
    }

    public function priceFor(string $component = 'full', string $currency = 'GBP'): ?TierPrice
    {
        return $this->prices->first(fn ($p) => $p->component === $component && $p->currency === $currency);
    }

    public function hasPrices(): bool
    {
        return $this->prices->contains(fn ($p) => $p->amount_minor !== null);
    }
}
