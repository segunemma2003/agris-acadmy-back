<?php

namespace App\Filament\Tutor\Resources\TopicResource\Pages;

use App\Filament\Tutor\Resources\TopicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

