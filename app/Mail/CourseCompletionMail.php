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
}
