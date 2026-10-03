<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];
    protected $casts = ['read_at' => 'datetime'];
    public function sender() { return $this->belongsTo(User::class, 'sender_user_id'); }
}
