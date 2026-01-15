<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeStudentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3; // Retry up to 3 times if it fails
    public $backoff = [60, 300]; // Wait 60 seconds, then 300 seconds between retries
    public $timeout = 30; // Timeout after 30 seconds
    public $maxExceptions = 2; // Max exceptions before marking as failed

    public function __construct(
        public User $user
    ) {}

    public function build()
    {
        return $this->subject('Welcome to Agrisiti Academy! 🎓')
            ->view('emails.welcome-student', [
                'user' => $this->user,
            ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure but don't throw - allow job to be marked as failed gracefully
        \Log::error('Welcome email failed to send', [
            'user_id' => $this->user->id,
            'user_email' => $this->user->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

