<?php

namespace App\Filament\TagDev\Resources\WeeklyReportResource\Pages;

use App\Filament\TagDev\Resources\WeeklyReportResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWeeklyReport extends ViewRecord
{
    protected static string $resource = WeeklyReportResource::class;

    // TagDev can only view, not edit
    protected function getHeaderActions(): array
    {
        return [];
    }
}
