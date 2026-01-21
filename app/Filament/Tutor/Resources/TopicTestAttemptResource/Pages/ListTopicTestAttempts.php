<?php

namespace App\Filament\Tutor\Resources\TopicTestAttemptResource\Pages;

use App\Filament\Tutor\Resources\TopicTestAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopicTestAttempts extends ListRecords
{
    protected static string $resource = TopicTestAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Test attempts are created by students, not tutors
        ];
    }
}
