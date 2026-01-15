<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseCompletionMail extends Mailable implements ShouldQueue
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
        return $this->subject('🎉 Congratulations! You\'ve Completed ' . $this->course->title)
            ->view('emails.course-completion', [
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
        \Log::error('Course completion email failed to send', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
