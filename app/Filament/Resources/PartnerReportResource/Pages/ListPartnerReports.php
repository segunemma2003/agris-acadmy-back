<?php

namespace App\Filament\Resources\PartnerReportResource\Pages;

use App\Filament\Resources\PartnerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerReports extends ListRecords
{
    protected static string $resource = PartnerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Send report to partner'),
        ];
    }
}
