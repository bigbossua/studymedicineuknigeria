<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime', 'created_at' => 'datetime'];
}
