<?php

namespace App\Filament\Tutor\Resources\WeeklyReportResource\Pages;

use App\Filament\Tutor\Resources\WeeklyReportResource;
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

