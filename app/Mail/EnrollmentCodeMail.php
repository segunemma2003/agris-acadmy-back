<?php

namespace App\Mail;

use App\Models\EnrollmentCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnrollmentCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EnrollmentCode $enrollmentCode
    ) {}

    public function build()
    {
        $course = $this->enrollmentCode->course;

        return $this->subject('Your Enrollment Code - ' . $course->title)
            ->view('emails.enrollment-code', [
                'code' => $this->enrollmentCode->code,
                'course' => $course,
                'expiresAt' => $this->enrollmentCode->expires_at,
            ]);
    }
}

