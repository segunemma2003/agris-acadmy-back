<?php

namespace App\Filament\Tutor\Resources\ModuleTestResource\Pages;

use App\Filament\Tutor\Resources\ModuleTestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModuleTest extends CreateRecord
{
    protected static string $resource = ModuleTestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tutor_id when creating a test
        $data['tutor_id'] = \Illuminate\Support\Facades\Auth::id();
        
        // If module_id is set, automatically set course_id from the module
        if (isset($data['module_id']) && !isset($data['course_id'])) {
            $module = \App\Models\Module::find($data['module_id']);
            if ($module) {
                $data['course_id'] = $module->course_id;
            }
        }
        return $data;
    }
}

