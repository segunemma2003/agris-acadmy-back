<?php

namespace App\Filament\Supervisor\Resources\TopicResource\Pages;

use App\Filament\Supervisor\Resources\TopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopics extends ListRecords
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

