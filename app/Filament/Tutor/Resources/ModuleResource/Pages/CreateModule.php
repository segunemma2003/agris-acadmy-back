<?php

namespace App\Filament\Tutor\Resources\ModuleResource\Pages;

use App\Filament\Tutor\Resources\ModuleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tutor_id when creating a module
        $data['tutor_id'] = Auth::id();
        return $data;
    }
}

