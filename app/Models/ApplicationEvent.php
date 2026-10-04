<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['payload' => 'array', 'created_at' => 'datetime'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function humanLabel(): string
    {
        return match ($this->type) {
            'application.started' => 'Application started',
            'step.completed' => 'Section completed: '.($this->payload['step_title'] ?? $this->payload['step'] ?? ''),
            'documents.generated' => 'Document checklist created',
            'document.uploaded' => 'Document uploaded: '.($this->payload['title'] ?? ''),
            'document.accepted' => 'Document accepted: '.($this->payload['title'] ?? ''),
            'document.rejected' => 'Document returned: '.($this->payload['title'] ?? ''),
            'document.requested' => 'Document requested: '.($this->payload['title'] ?? ''),
            'payment.initiated' => 'Payment started',
            'payment.succeeded' => 'Payment received',
            'payment.failed' => 'Payment failed',
            'payment.expired' => 'Checkout expired without payment',
            'payment.refunded' => 'Refund issued: '.($this->payload['refunded'] ?? ''),
            'payment.amount_mismatch' => 'Payment held for checking by our team',
            'payment.disputed' => 'Payment disputed with the card issuer',
            'payment.manual_requested' => 'Bank transfer requested',
            'service.changed' => 'Service changed to '.($this->payload['to'] ?? ''),
            'service.chosen' => 'Service chosen: '.($this->payload['to'] ?? ''),
            'services.approved' => 'Profile reviewed: service options and fees shown to the student',
            'services.approval_withdrawn' => 'Service approval withdrawn',
            'profile.ready' => 'Profile complete and ready for review',
            'review.started' => 'Review started by our team',
            'action.required' => 'Action required',
            'approval.requested' => 'Your approval requested',
            'student.approved' => 'You approved the application',
            'approval.declined' => 'You requested changes',
            'authorisation.invalidated' => 'Approval reset because the package changed',
            'submission.sent' => 'Submitted to the university',
            'university.response' => 'University response recorded',
            'message.sent' => 'Message sent',
            'application.withdrawn' => 'Application withdrawn',
            default => str_replace(['.', '_'], ' ', ucfirst($this->type)),
        };
    }
}
