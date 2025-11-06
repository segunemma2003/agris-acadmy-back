<?php

namespace App\Filament\Tutor\Resources\MessageResource\Pages;

use App\Filament\Tutor\Resources\MessageResource;
use App\Models\Message;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-arrow-uturn-left')
                ->form([
                    Forms\Components\RichEditor::make('reply_message')
                        ->label('Your Reply')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Create a reply message
                    Message::create([
                        'course_id' => $this->record->course_id,
                        'sender_id' => Auth::id(),
                        'recipient_id' => $this->record->sender_id,
                        'subject' => 'Re: ' . $this->record->subject,
                        'message' => $data['reply_message'],
                        'is_read' => false,
                    ]);

                    // Mark original as read
                    $this->record->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Reply sent successfully')
                        ->success()
                        ->send();

                    $this->redirect(MessageResource::getUrl('index'));
                }),
            Actions\EditAction::make(),
        ];
    }
}

