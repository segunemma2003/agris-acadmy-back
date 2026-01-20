<?php

namespace App\Filament\Tutor\Resources\MessageResource\Pages;

use App\Filament\Tutor\Resources\MessageResource;
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
        // Store send_to_all in session for afterCreate
        if (isset($data['send_to_all']) && $data['send_to_all']) {
            session(['send_to_all_' . Auth::id() => true]);
            session(['message_data_' . Auth::id() => $data]);
            // Set temporary recipient_id
            $data['recipient_id'] = Auth::id();
        } else {
            session(['send_to_all_' . Auth::id() => false]);
        }
        // Remove send_to_all as it's not a database field
        unset($data['send_to_all']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $message = $this->record;
        $tutor = Auth::user();
        $course = Course::find($message->course_id);

        if (!$course) {
            return;
        }

        $sendToAll = session('send_to_all_' . Auth::id(), false);
        $messageData = session('message_data_' . Auth::id());

        // Clear session
        session()->forget(['send_to_all_' . Auth::id(), 'message_data_' . Auth::id()]);

        if ($sendToAll) {
            // Send to all enrolled students
            $enrollments = \App\Models\Enrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            // Delete the temporary message record
            $message->delete();

            foreach ($enrollments as $enrollment) {
                if ($enrollment->user && $enrollment->user->email) {
                    try {
                        // Create a message record for each recipient
                        \App\Models\Message::create([
                            'course_id' => $course->id,
                            'sender_id' => $tutor->id,
                            'recipient_id' => $enrollment->user->id,
                            'subject' => $messageData['subject'] ?? 'Message from Your Tutor',
                            'message' => $messageData['message'] ?? '',
                            'is_read' => false,
                        ]);

                        // Send email
                        Mail::to($enrollment->user->email)->queue(new TutorNotificationMail(
                            $enrollment->user,
                            $course,
                            $messageData['subject'] ?? 'Message from Your Tutor',
                            $messageData['message'] ?? '',
                            $tutor
                        ));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send tutor group message email notification', [
                            'user_id' => $enrollment->user->id,
                            'course_id' => $course->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } else {
            // Send to individual recipient
            $recipient = User::find($message->recipient_id);
            if ($recipient && $recipient->email) {
                try {
                    Mail::to($recipient->email)->queue(new TutorNotificationMail(
                        $recipient,
                        $course,
                        $message->subject ?? 'Message from Your Tutor',
                        $message->message,
                        $tutor
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send tutor message email notification', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}

