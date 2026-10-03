<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionChoice extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
