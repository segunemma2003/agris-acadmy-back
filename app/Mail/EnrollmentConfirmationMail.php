<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnrollmentConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 30;
    public $maxExceptions = 2;

    public function __construct(
        public User $user,
        public Course $course,
        public Enrollment $enrollment
    ) {}

    public function build()
    {
        return $this->subject('🎉 You\'re Enrolled! Welcome to ' . $this->course->title)
            ->view('emails.enrollment-confirmation', [
                'user' => $this->user,
                'course' => $this->course,
                'enrollment' => $this->enrollment,
            ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Enrollment confirmation email failed to send', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}



