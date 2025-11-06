<?php

namespace App\Filament\Tutor\Resources\ModuleResource\Pages;

use App\Filament\Tutor\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

