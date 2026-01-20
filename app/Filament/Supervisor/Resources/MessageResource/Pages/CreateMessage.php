<?php

namespace App\Filament\Supervisor\Resources\MessageResource\Pages;

use App\Filament\Supervisor\Resources\MessageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\TutorNotificationMail;
use App\Models\User;
use App\Models\Course;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sender_id'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $message = $this->record;
        $supervisor = Auth::user();
        $recipient = User::find($message->recipient_id);
        $course = Course::find($message->course_id);

        // Send email notification
        if ($recipient && $recipient->email && $course) {
            try {
                Mail::to($recipient->email)->queue(new TutorNotificationMail(
                    $recipient,
                    $course,
                    $message->subject ?? 'Message from Your Facilitator',
                    $message->message,
                    $supervisor
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send supervisor message email notification', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

