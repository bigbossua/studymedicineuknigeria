<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    protected $casts = ['succeeded_at' => 'datetime'];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function tierPrice()
    {
        return $this->belongsTo(TierPrice::class, 'tier_price_id');
    }

    public function formattedAmount(): string
    {
        $symbol = ['GBP' => '£', 'NGN' => '₦', 'USD' => '$'][$this->currency] ?? $this->currency.' ';

        return $symbol.number_format($this->amount_minor / 100, 2);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'REQUIRED' => 'Payment required', 'INITIATED' => 'Payment started — awaiting confirmation', 'SUCCEEDED' => 'Paid',
            'FAILED' => 'Payment failed', 'EXPIRED' => 'Checkout expired', 'REFUNDED_PARTIAL' => 'Partly refunded', 'REFUNDED_FULL' => 'Refunded',
            'DISPUTED' => 'Under dispute', 'MANUAL_REVIEW' => 'Bank transfer — awaiting confirmation', 'REJECTED' => 'Not confirmed',
            default => $this->status,
        };
    }
}
