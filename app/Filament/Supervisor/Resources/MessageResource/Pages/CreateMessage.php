<?php

namespace App\Filament\Supervisor\Resources\MessageResource\Pages;

use App\Filament\Supervisor\Resources\MessageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sender_id'] = Auth::id();
        return $data;
    }
}

