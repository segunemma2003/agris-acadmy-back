<?php

namespace App\Filament\Tutor\Resources\TopicTestResource\Pages;

use App\Filament\Tutor\Resources\TopicTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopicTests extends ListRecords
{
    protected static string $resource = TopicTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
