<?php

namespace App\Models;

use App\Enums\Stage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $guarded = [];

    protected $casts = [
        'form' => 'array', 'section_status' => 'array', 'withdrawn_at' => 'datetime', 'closed_at' => 'datetime',
        'hold_until' => 'date', 'last_activity_at' => 'datetime', 'stage' => Stage::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ServiceTier::class, 'service_tier_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class)->latest('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function authorisations(): HasMany
    {
        return $this->hasMany(Authorisation::class)->latest('approved_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function getRouteKeyName(): string
    {
        return 'application_number';
    }

    public static function nextNumber(int $intakeYear): string
    {
        $seq = (int) (static::max('id') ?? 0) + 1;

        return sprintf('SMN-%d-%06d', $intakeYear, $seq);
    }

    public function record(string $type, array $payload = [], ?int $actorId = null): ApplicationEvent
    {
        $this->forceFill(['last_activity_at' => now()])->saveQuietly();

        return $this->events()->create(['type' => $type, 'payload' => $payload, 'actor_user_id' => $actorId]);
    }

    public function formSection(string $step): array
    {
        return $this->form[$step] ?? [];
    }

    public function sectionComplete(string $step): bool
    {
        return ($this->section_status[$step] ?? null) === 'complete';
    }

    public function hasSucceededPayment(?string $component = null): bool
    {
        return $this->payments->contains(fn (Payment $p) => $p->status === 'SUCCEEDED' && ($component === null || $p->tierPrice?->component === $component || $p->tierPrice?->component === 'full'));
    }

    public function isTerminal(): bool
    {
        return in_array($this->stage, [Stage::WITHDRAWN, Stage::CLOSED, Stage::COMPLETED], true);
    }
}
