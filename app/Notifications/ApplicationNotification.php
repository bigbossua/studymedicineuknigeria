<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** One branded transactional email per real system event (brief 113). */
class ApplicationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application, public string $type, public array $data = []) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->application;
        $n = $a->application_number;
        $d = $this->data;
        [$subject, $lines, $action, $url] = match ($this->type) {
            'application.started' => ["Your application has been started — $n", ["Your application number is $n. Keep it for every message with us.", 'Your progress saves automatically. You can leave and return at any time.'], 'Continue your application', route('portal.dashboard')],
            'document.requested' => ["Document required: {$d['title']} — $n", ["Our team has asked for: {$d['title']}.", $d['reason'] ?? ''], 'Upload now', route('portal.documents.index', $a)],
            'document.accepted' => ["Document accepted: {$d['title']} — $n", ["We have accepted your {$d['title']}."], 'View your documents', route('portal.documents.index', $a)],
            'document.rejected' => ["Action required: {$d['title']} — $n", ["Your {$d['title']} needs to be replaced.", 'Reason: '.($d['reason'] ?? 'see your portal')], 'Replace document', route('portal.documents.index', $a)],
            'documents.complete' => ["Documents complete — $n", ['Every required document has been accepted. Your file is now in review with our team. Nothing is needed from you right now.'], 'View your application', route('portal.dashboard')],
            'services.approved' => ["Your profile has been reviewed: choose your service — $n", ['Our team has reviewed your profile. Your portal now shows the service options open to you, each with its exact fee, what it includes and what it does not.', 'Choose the one that suits you; nothing is charged until you confirm and pay.', $d['note'] ?? ''], 'Choose your service', route('portal.services.index', $a)],
            'payment.succeeded' => ["Payment received — $n", ['Thank you. We have received your payment of '.($d['amount'] ?? '').'. A receipt from Stripe follows separately.'], 'View payments', route('portal.payments.index', $a)],
            'approval.requested' => ["Please review and approve your application — $n", ['Your application package is ready. Please review every detail and document, then approve it so submission can begin.', 'Nothing is submitted to any university until you approve.'], 'Review and approve', route('portal.approve.show', $a)],
            'student.approved' => ["Approval recorded — $n", ['We have recorded your approval on '.($d['at'] ?? now()->format('j F Y')).'. We are now preparing the final package.'], 'Track submission', route('portal.submissions.index', $a)],
            'submission.sent' => ["Submitted to {$d['university']} — $n", ["Your application to {$d['university']} ({$d['course']}) has been submitted by the route agreed with you.", 'We will update you when the university responds.'], 'Track submission', route('portal.submissions.index', $a)],
            'university.response' => ["University update: {$d['status']} — $n", ["{$d['university']} has updated your application status to: {$d['status']}.", $d['note'] ?? ''], 'View details', route('portal.submissions.index', $a)],
            'action.required' => ["Action required — $n", ['Our team needs something from you to continue.', $d['note'] ?? ''], 'Open your portal', route('portal.dashboard')],
            'message.received' => ["New message from our team — $n", ['You have a new message in your portal.'], 'Read message', route('portal.messages.index', $a)],
            'reminder' => ["Your application is waiting — $n", ['You have an application in progress. Your work is saved; pick up where you left off whenever you are ready.', $d['next'] ?? ''], 'Continue', route('portal.dashboard')],
            default => ["Update on your application — $n", [$d['note'] ?? 'There is an update on your application.'], 'Open your portal', route('portal.dashboard')],
        };
        $m = (new MailMessage)->subject($subject)->greeting('Dear '.explode(' ', $notifiable->name)[0].',');
        foreach (array_filter($lines) as $l) {
            $m->line($l);
        }
        $m->action($action, $url);
        $m->line('This email was sent because of an event on your application. Admission decisions are made solely by universities.');
        $m->salutation('Study Medicine UK Nigeria · '.config('site.email'));

        return $m;
    }
}
