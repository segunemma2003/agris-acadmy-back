<?php

namespace App\Filament\Supervisor\Resources\StudentResource\Pages;

use App\Filament\Supervisor\Resources\StudentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;
}

