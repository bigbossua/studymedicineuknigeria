<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $guarded = [];

    protected $casts = ['status' => DocumentStatus::class];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DocumentEvent::class)->latest('created_at');
    }

    public function transition(DocumentStatus $to, ?int $actorId = null, ?string $reason = null): void
    {
        $from = $this->status;
        $this->status = $to;
        if ($reason !== null) {
            $this->staff_note = $reason;
        }
        $this->save();
        $this->events()->create(['from_status' => $from?->value, 'to_status' => $to->value, 'actor_user_id' => $actorId, 'reason' => $reason]);
    }
}
