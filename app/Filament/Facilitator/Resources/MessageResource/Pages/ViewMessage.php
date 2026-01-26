<?php

namespace App\Filament\Facilitator\Resources\MessageResource\Pages;

use App\Filament\Facilitator\Resources\MessageResource;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

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
                    
                    Message::create([
                        'course_id' => $parentMessage->course_id,
                        'sender_id' => Auth::id(),
                        'recipient_id' => $recipientId,
                        'parent_id' => $parentMessage->id,
                        'subject' => 'Re: ' . $parentMessage->subject,
                        'message' => $data['message'],
                        'is_read' => false,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Reply sent successfully')
                        ->success()
                        ->send();

                    $this->refresh();
                })
                ->modalHeading('Reply to Message')
                ->modalSubmitActionLabel('Send Reply'),
        ];
    }
}

