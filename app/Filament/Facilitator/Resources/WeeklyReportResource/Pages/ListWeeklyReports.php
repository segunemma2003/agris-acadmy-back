<?php

namespace App\Filament\Facilitator\Resources\WeeklyReportResource\Pages;

use App\Filament\Facilitator\Resources\WeeklyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyReports extends ListRecords
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
