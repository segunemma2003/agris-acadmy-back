<?php

namespace App\Filament\Resources\PartnerReportResource\Pages;

use App\Filament\Resources\PartnerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnerReport extends ViewRecord
{
    protected static string $resource = PartnerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
