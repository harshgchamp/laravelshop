<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * WelcomeAdminCreatedUser — Mailable
 *
 * Sent when an admin creates a new user account.
 *
 * WHY Queueable + SerializesModels?
 *  - Queueable: allows this mailable to be pushed onto the queue if sent via queue().
 *  - SerializesModels: when the job is serialized into the `jobs` table, Eloquent models
 *    are stored as { class, id } rather than the full model dump. When the worker
 *    picks up the job, it re-fetches the model from the DB by ID — ensures fresh data
 *    and avoids storing sensitive fields in the queue payload.
 *
 * The mailable receives both $user (the new account) and $createdBy (the admin)
 * so the email can say "Your account was created by John Admin."
 */
class WelcomeAdminCreatedUser extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly User $createdBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your admin account has been created',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-admin-created-user',
        );
    }
}
