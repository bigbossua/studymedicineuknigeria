<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $guarded = [];

    protected $casts = ['scheduled_for' => 'datetime', 'sent_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
