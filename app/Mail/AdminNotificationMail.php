<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 30;
    public $maxExceptions = 2;

    public function __construct(
        public User $recipient,
        public string $emailSubject,
        public string $message,
        public ?User $sender = null
    ) {}

    public function build()
    {
        return $this->subject($this->emailSubject)
            ->view('emails.admin-notification', [
                'recipient' => $this->recipient,
                'message' => $this->message,
                'sender' => $this->sender,
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Admin notification email failed to send', [
            'recipient_id' => $this->recipient->id,
            'error' => $exception->getMessage(),
        ]);
    }
}



