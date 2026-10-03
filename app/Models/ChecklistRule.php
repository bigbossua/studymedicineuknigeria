<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistRule extends Model
{
    protected $guarded = [];

    protected $casts = ['predicate' => 'array', 'require_codes' => 'array', 'active' => 'boolean'];
}
