<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    public const ROUTES = [
        'UCAS_STUDENT' => 'UCAS — you submit in your own UCAS Hub account; we prepare everything and guide you',
        'DIRECT_PORTAL_STUDENT' => 'Direct to the university — you submit through the university\'s own application form; we prepare the package',
        'UCAS_CENTRE' => 'UCAS via our registered centre (not currently available)',
        'DIRECT_AGENT' => 'Submitted by us under a signed agreement with the university (only where an agreement is on file)',
        'PATHWAY_PROVIDER' => 'Foundation or pathway provider application',
    ];

    protected $guarded = [];
    protected $casts = ['submitted_at' => 'datetime'];

    public function application() { return $this->belongsTo(Application::class); }
    public function university() { return $this->belongsTo(University::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function choices() { return $this->hasMany(SubmissionChoice::class)->orderBy('choice_order'); }
    public function events() { return $this->hasMany(SubmissionEvent::class)->latest('created_at'); }
    public function authorisation() { return $this->belongsTo(Authorisation::class); }

    public function transition(string $to, ?int $actorId = null, ?string $note = null, ?string $ref = null): void
    {
        $from = $this->status;
        $this->status = $to;
        if ($ref) $this->external_reference = $ref;
        if ($to === 'SUBMITTED') { $this->submitted_at = now(); $this->submitted_by = $actorId ? (string) $actorId : 'STUDENT'; }
        $this->save();
        $this->events()->create(['from_status' => $from, 'to_status' => $to, 'actor_user_id' => $actorId, 'note' => $note, 'external_reference' => $ref]);
    }

    public function routeLabel(): string { return self::ROUTES[$this->route_code] ?? $this->route_code; }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'PROPOSED' => 'Proposed — awaiting your approval', 'AUTHORISED' => 'Approved by you', 'PACKAGE_READY' => 'Package prepared', 'SUBMITTED' => 'Submitted',
            'ACKNOWLEDGED' => 'Acknowledged by the university', 'INTERVIEW' => 'Interview stage', 'OFFER_CONDITIONAL' => 'Conditional offer', 'OFFER_UNCONDITIONAL' => 'Unconditional offer',
            'REJECTED' => 'Unsuccessful', 'WAITLISTED' => 'Waiting list', 'ACCEPTED_BY_STUDENT' => 'Offer accepted', 'DECLINED' => 'Offer declined', 'CLOSED' => 'Closed', default => $this->status,
        };
    }
}
