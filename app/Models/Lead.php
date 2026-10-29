<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    /** The Contact page promises a reply within this many working days. */
    public const RESPONSE_WORKING_DAYS = 2;

    protected $guarded = [];

    protected $casts = ['utm' => 'array', 'eligibility_answers' => 'array', 'eligibility_result' => 'array', 'consent_marketing' => 'boolean'];

    /** Working days (Monday to Friday; UK bank holidays are not excluded) since the lead arrived. */
    public function workingDaysWaiting(?CarbonInterface $now = null): int
    {
        $now ??= now();

        return $this->created_at->isAfter($now) ? 0 : $this->created_at->copy()->startOfDay()->diffInWeekdays($now->copy()->startOfDay());
    }

    /** A lead still marked new after the promised reply time. */
    public function isOverdue(?CarbonInterface $now = null): bool
    {
        return $this->status === 'new' && $this->workingDaysWaiting($now) > self::RESPONSE_WORKING_DAYS;
    }

    /** @return Collection<int, Lead> */
    public static function overdue(): Collection
    {
        return static::where('status', 'new')->where('created_at', '<', now()->subDays(self::RESPONSE_WORKING_DAYS))->get()->filter->isOverdue()->values();
    }
}
