<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Laravel's password-reset email, sent by the queue for the same reason as QueuedVerifyEmail. */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 120;
}
