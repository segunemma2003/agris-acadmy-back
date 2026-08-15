<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GraduateFundingEligibleMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300];

    public function __construct(
        public Certificate $certificate,
        public float $eligibleLoanAmount,
        public string $registerUrl,
        public string $language = 'en',
    ) {}

    public function build()
    {
        $this->certificate->loadMissing(['user', 'course']);

        $courseTitle = $this->certificate->course?->title ?? '';
        $isHa = $this->language === 'ha';
        $subject = $isHa
            ? "Ka cancanci rancen digiri bayan kammala '{$courseTitle}'"
            : "You're eligible for a graduate loan after completing '{$courseTitle}'";

        return $this->subject($subject)
            ->view('emails.graduate-funding-eligible', [
                'certificate' => $this->certificate,
                'user' => $this->certificate->user,
                'course' => $this->certificate->course,
                'locale' => $this->language,
                'eligibleLoanAmount' => $this->eligibleLoanAmount,
                'eligibleLoanAmountFormatted' => number_format($this->eligibleLoanAmount, 0, '.', ','),
                'registerUrl' => $this->registerUrl,
            ]);
    }
}
