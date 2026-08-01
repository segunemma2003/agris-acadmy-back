<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LearnerInterventionAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\LearnerInterventionAlert>  $alerts
     */
    public function __construct(public Collection $alerts) {}

    public function build()
    {
        $count = $this->alerts->count();

        return $this->subject("Agrisiti Academy — {$count} learner intervention alert" . ($count === 1 ? '' : 's'))
            ->view('emails.learner-intervention-alert', [
                'alerts' => $this->alerts,
                'generatedAt' => now(),
            ]);
    }
}
