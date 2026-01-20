<?php

namespace App\Filament\Tutor\Resources\TopicTestResource\Pages;

use App\Filament\Tutor\Resources\TopicTestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTopicTest extends CreateRecord
{
    protected static string $resource = TopicTestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tutor_id when creating a topic test
        $data['tutor_id'] = Auth::id();
        return $data;
    }
}
