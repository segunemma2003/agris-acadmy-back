<?php

namespace App\Filament\Resources\TopicTestResource\Pages;

use App\Filament\Resources\TopicTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTopicTest extends EditRecord
{
    protected static string $resource = TopicTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to topic')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => \App\Filament\Resources\TopicResource::getUrl('edit', ['record' => $this->record->topic_id])),
            Actions\DeleteAction::make()
                ->successRedirectUrl(fn () => \App\Filament\Resources\TopicResource::getUrl('edit', ['record' => $this->record->topic_id])),
        ];
    }
}
