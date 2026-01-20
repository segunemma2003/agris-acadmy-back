<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TutorNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 30;
    public $maxExceptions = 2;

    public function __construct(
        public User $recipient,
        public Course $course,
        public string $subject,
        public string $message,
        public User $tutor
    ) {}

    public function build()
    {
        return $this->subject($this->subject)
            ->view('emails.tutor-notification', [
                'recipient' => $this->recipient,
                'course' => $this->course,
                'message' => $this->message,
                'tutor' => $this->tutor,
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Tutor notification email failed to send', [
            'recipient_id' => $this->recipient->id,
            'course_id' => $this->course->id,
            'tutor_id' => $this->tutor->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

