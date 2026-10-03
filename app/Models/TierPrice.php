<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TierPrice extends Model
{
    protected $guarded = [];
    protected $casts = ['active' => 'boolean'];

    public function tier() { return $this->belongsTo(ServiceTier::class, 'service_tier_id'); }

    public function formatted(): string
    {
        if ($this->amount_minor === null) return 'Price to be confirmed';
        $symbol = ['GBP' => '£', 'NGN' => '₦', 'USD' => '$'][$this->currency] ?? $this->currency.' ';

        return $symbol.number_format($this->amount_minor / 100, 0);
    }
}
