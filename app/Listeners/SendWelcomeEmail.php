<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedByAdmin;
use App\Mail\WelcomeAdminCreatedUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SendWelcomeEmail — Queued Listener
 *
 * Sends a welcome email to a new user created by an admin.
 *
 * THE KEY DIFFERENCE FROM LogAdminActivity:
 *  - Implements ShouldQueue → Laravel does NOT call handle() inline.
 *    Instead, it serializes the event and pushes a job to the `jobs` table.
 *    A queue worker (php artisan queue:work) picks it up in the background.
 *  - The HTTP response returns to the browser BEFORE the email is sent.
 *    Users get instant feedback; the email goes out moments later.
 *
 * WHY queue the email but not the log?
 *  - Activity logs must be written immediately — they're your audit trail.
 *    If the queue worker is down, you can't lose the audit record.
 *  - Emails can tolerate a few seconds of delay. SMTP calls are slow (100ms–2s)
 *    and shouldn't block the admin's HTTP request.
 *
 * RETRY BEHAVIOUR:
 *  - $tries = 3     → attempt up to 3 times before giving up
 *  - $backoff = 60  → wait 60 seconds between retry attempts
 *                     (gives a flaky SMTP server time to recover)
 *
 * FAILURE HANDLING:
 *  - failed() fires after ALL retries are exhausted.
 *  - The job record moves to the `failed_jobs` table automatically.
 *  - We log the error so it shows up in Laravel's log file for debugging.
 *    In production you'd also alert an on-call channel here.
 */
class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;

    public function handle(UserCreatedByAdmin $event): void
    {
        Mail::to($event->user->email)
            ->send(new WelcomeAdminCreatedUser($event->user, $event->performedBy));
    }

    /**
     * Called after all $tries are exhausted.
     * The failed job is stored in the `failed_jobs` table automatically by Laravel.
     * Here we add a log entry so the error is visible without opening a DB client.
     */
    public function failed(UserCreatedByAdmin $event, \Throwable $exception): void
    {
        Log::error('SendWelcomeEmail failed after all retries', [
            'user_id'   => $event->user->id,
            'user_email'=> $event->user->email,
            'error'     => $exception->getMessage(),
        ]);
    }
}
