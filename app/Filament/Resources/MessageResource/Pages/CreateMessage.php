<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminNotificationMail;
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
        $sender = Auth::user();
        $recipient = User::find($message->recipient_id);
        $course = Course::find($message->course_id);

        // Send email notification
        if ($recipient && $recipient->email) {
            try {
                // Admin sending to any user
                if ($sender->role === 'admin') {
                    Mail::to($recipient->email)->queue(new AdminNotificationMail(
                        $recipient,
                        $message->subject ?? 'Notification from Agrisiti Academy',
                        $message->message,
                        $sender
                    ));
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send message email notification', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}



