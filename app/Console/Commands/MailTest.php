<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one plain test message through the configured mailer, synchronously, to prove that verification and reset
 * emails leave this server. Refuses the log/array mailers (they deliver nothing). Prints the outcome, never a password.
 */
class MailTest extends Command
{
    protected $signature = 'smukn:mail-test {to : Address to send the test message to}';

    protected $description = 'Send one test email through the configured mailer (refuses log/array)';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Not an email address.');

            return self::FAILURE;
        }
        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->error("MAIL_MAILER is {$mailer}: nothing would be delivered. Set the mailbox password with Update server settings.");

            return self::FAILURE;
        }
        $from = (string) config('mail.from.address');
        try {
            Mail::mailer($mailer)->raw('This is a test message from '.config('app.url').' ('.app()->environment().') sent at '.now()->toDateTimeString()." UTC.\n\nIf it reached your inbox (not spam), verification and password-reset emails will reach students too. In Gmail, \"Show original\" should read SPF: PASS, DKIM: PASS, DMARC: PASS.", function ($m) use ($to) {
                $m->to($to)->subject('Study Medicine UK Nigeria: email delivery test');
            });
        } catch (\Throwable $e) {
            $this->error('Sending failed ('.class_basename($e).'): '.str_replace((string) config('mail.mailers.smtp.password'), '[redacted]', $e->getMessage()));

            return self::FAILURE;
        }
        $this->info("Accepted by the {$mailer} server for delivery from {$from} to {$to}. Check that inbox (and its spam folder).");

        return self::SUCCESS;
    }
}
