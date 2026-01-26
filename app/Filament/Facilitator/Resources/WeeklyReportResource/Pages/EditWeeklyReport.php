<?php

namespace App\Filament\Facilitator\Resources\WeeklyReportResource\Pages;

use App\Filament\Facilitator\Resources\WeeklyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyReport extends EditRecord
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
