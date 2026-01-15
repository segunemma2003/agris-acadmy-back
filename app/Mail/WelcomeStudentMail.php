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
}

