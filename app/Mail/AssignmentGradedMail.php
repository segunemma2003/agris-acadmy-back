<?php

namespace App\Mail;

use App\Models\AssignmentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AssignmentGradedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 30;
    public $maxExceptions = 2;

    public function __construct(public AssignmentSubmission $submission) {}

    public function build()
    {
        $assignment = $this->submission->assignment;

        return $this->subject("Assignment Graded: {$assignment->title}")
            ->view('emails.assignment-graded', [
                'user' => $this->submission->user,
                'assignment' => $assignment,
                'course' => $assignment->course,
                'submission' => $this->submission,
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Assignment graded email failed to send', [
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
