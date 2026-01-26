<?php

namespace App\Filament\Facilitator\Resources\StudentResource\Pages;

use App\Filament\Facilitator\Resources\StudentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;
}

