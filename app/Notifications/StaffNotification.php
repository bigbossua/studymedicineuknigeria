<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $subject, public array $lines, public ?string $url = null) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)->subject('[SMUKN] '.$this->subject);
        foreach ($this->lines as $l) $m->line($l);
        if ($this->url) $m->action('Open in admin', $this->url);

        return $m;
    }
}
