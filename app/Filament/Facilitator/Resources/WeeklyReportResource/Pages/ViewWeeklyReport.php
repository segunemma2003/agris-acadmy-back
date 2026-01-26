<?php

namespace App\Filament\Facilitator\Resources\WeeklyReportResource\Pages;

use App\Filament\Facilitator\Resources\WeeklyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWeeklyReport extends ViewRecord
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}


