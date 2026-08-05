<?php

namespace App\Filament\Resources\PartnerReportResource\Pages;

use App\Filament\Resources\PartnerReportResource;
use App\Services\PartnerReportSheetImporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerReports extends ListRecords
{
    protected static string $resource = PartnerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadParticipantTemplate')
                ->label('Participant Excel template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => PartnerReportSheetImporter::downloadParticipantTemplate()),
            Actions\Action::make('downloadLinksTemplate')
                ->label('Links Excel template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => PartnerReportSheetImporter::downloadLinksTemplate()),
            Actions\CreateAction::make()
                ->label('Send report to partner'),
        ];
    }
}
