<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewModuleNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 30;
    public $maxExceptions = 2;

    public function __construct(
        public User $user,
        public Course $course,
        public Module $module
    ) {}

    public function build()
    {
        return $this->subject('📚 New Module Added: ' . $this->module->title . ' - ' . $this->course->title)
            ->view('emails.new-module-notification', [
                'user' => $this->user,
                'course' => $this->course,
                'module' => $this->module,
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('New module notification email failed to send', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'module_id' => $this->module->id,
            'error' => $exception->getMessage(),
        ]);
    }
}



