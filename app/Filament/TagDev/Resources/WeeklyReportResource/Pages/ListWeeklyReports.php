<?php

namespace App\Filament\TagDev\Resources\WeeklyReportResource\Pages;

use App\Filament\TagDev\Resources\WeeklyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyReports extends ListRecords
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions - read-only dashboard
        ];
    }
}
