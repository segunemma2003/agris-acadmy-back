<?php

namespace App\Filament\Tutor\Resources\ModuleTestResource\Pages;

use App\Filament\Tutor\Resources\ModuleTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModuleTests extends ListRecords
{
    protected static string $resource = ModuleTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

