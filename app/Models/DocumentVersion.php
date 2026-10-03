<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['uploaded_at' => 'datetime', 'encrypted' => 'boolean'];

    public function document() { return $this->belongsTo(Document::class); }
}
