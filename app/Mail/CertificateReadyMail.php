<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificateReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 30;
    public $maxExceptions = 2;

    public function __construct(
        public Certificate $certificate,
        public bool $isAdminCopy = false,
    ) {}

    public function build()
    {
        $courseTitle = $this->certificate->course->title;
        $recipientName = $this->certificate->recipient_name;

        $subject = $this->isAdminCopy
            ? "Certificate generated: {$recipientName} - {$courseTitle}"
            : "Your certificate for '{$courseTitle}' is ready 🎓";

        return $this->subject($subject)
            ->view('emails.certificate-ready', [
                'certificate' => $this->certificate,
                'user' => $this->certificate->user,
                'course' => $this->certificate->course,
                'isAdminCopy' => $this->isAdminCopy,
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Certificate ready email failed to send', [
            'certificate_id' => $this->certificate->id,
            'is_admin_copy' => $this->isAdminCopy,
            'error' => $exception->getMessage(),
        ]);
    }
}
