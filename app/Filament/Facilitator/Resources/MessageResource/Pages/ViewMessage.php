<?php

namespace App\Filament\Facilitator\Resources\MessageResource\Pages;

use App\Filament\Facilitator\Resources\MessageResource;
use App\Mail\TutorNotificationMail;
use App\Models\Message;
use App\Models\User;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load replies relationship
        $this->record->load('replies.sender');
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-arrow-uturn-left')
                ->form([
                    Forms\Components\RichEditor::make('message')
                        ->required()
                        ->label('Your Reply')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $parentMessage = $this->record;
                    
                    // Determine recipient: if current user is the sender, reply to recipient; otherwise reply to sender
                    $recipientId = Auth::id() === $parentMessage->sender_id 
                        ? $parentMessage->recipient_id 
                        : $parentMessage->sender_id;
                    
                    $reply = Message::create([
                        'course_id' => $parentMessage->course_id,
                        'sender_id' => Auth::id(),
                        'recipient_id' => $recipientId,
                        'parent_id' => $parentMessage->id,
                        'subject' => 'Re: ' . $parentMessage->subject,
                        'message' => $data['message'],
                        'is_read' => false,
                    ]);

                    // Send same message to recipient's email
                    $recipient = User::find($recipientId);
                    $course = Course::find($parentMessage->course_id);
                    if ($recipient && $recipient->email && $course) {
                        try {
                            Mail::to($recipient->email)->queue(new TutorNotificationMail(
                                $recipient,
                                $course,
                                'Re: ' . $parentMessage->subject,
                                strip_tags($data['message']),
                                Auth::user()
                            ));
                        } catch (\Exception $e) {
                            Log::error('Failed to send reply email notification', [
                                'reply_id' => $reply->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Reply sent successfully')
                        ->success()
                        ->send();

                    // Refresh the record and reload replies
                    $this->record->refresh();
                    $this->record->load('replies.sender');
                    $this->fillForm();
                })
                ->modalHeading('Reply to Message')
                ->modalSubmitActionLabel('Send Reply'),
        ];
    }
}

