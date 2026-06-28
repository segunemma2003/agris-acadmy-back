<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FacilitatorQueueAlert extends Notification
{
    use Queueable;

    public function __construct(protected User $student) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New student awaiting facilitator assignment')
            ->line("A new student could not be automatically assigned a facilitator.")
            ->line("**Name:** {$this->student->name}")
            ->line("**State:** {$this->student->state}")
            ->line("**LGA:** {$this->student->lga}")
            ->action('Review in Admin Panel', url('/admin/users/' . $this->student->id));
    }
}
