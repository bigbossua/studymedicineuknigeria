<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunnelEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['occurred_at' => 'datetime', 'utm' => 'array', 'properties' => 'array'];

    /** Ordered funnel (docs/architecture/12.9). Steps outside this list are still recorded. */
    public const ORDER = [
        'course_viewed', 'apply_viewed', 'lead_created', 'account_created', 'application_started', 'step_completed', 'document_uploaded',
        'document_accepted', 'payment_started', 'payment_completed', 'approval_requested', 'student_approved',
        'submitted', 'university_response',
    ];
}
