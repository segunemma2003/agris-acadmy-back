<?php

namespace App\Filament\Supervisor\Resources\AssignmentResource\Pages;

use App\Filament\Supervisor\Resources\AssignmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tutor_id'] = Auth::id();
        return $data;
    }
}

