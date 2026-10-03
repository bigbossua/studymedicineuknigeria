<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAction extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'created_at' => 'datetime'];

    public function admin() { return $this->belongsTo(User::class, 'admin_user_id'); }

    public static function log(string $action, ?Model $target = null, array $payload = []): void
    {
        static::create(['admin_user_id' => auth()->id(), 'action' => $action, 'target_type' => $target ? $target::class : null, 'target_id' => $target?->getKey(), 'payload' => $payload]);
    }
}
