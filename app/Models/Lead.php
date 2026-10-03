<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $guarded = [];

    protected $casts = ['utm' => 'array', 'eligibility_answers' => 'array', 'eligibility_result' => 'array', 'consent_marketing' => 'boolean'];
}
