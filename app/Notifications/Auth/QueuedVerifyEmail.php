<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Laravel's verification email, sent by the queue (processed every minute by the scheduler) so a mail outage or
 * missing mail settings never turns a registration into an error page; failed sends are retried.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 120;
}
