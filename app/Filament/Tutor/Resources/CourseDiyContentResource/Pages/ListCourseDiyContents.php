<?php

namespace App\Filament\Tutor\Resources\CourseDiyContentResource\Pages;

use App\Filament\Tutor\Resources\CourseDiyContentResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseDiyContents extends ListRecords
{
    protected static string $resource = CourseDiyContentResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()->label('New DIY practical')];
    }
}
