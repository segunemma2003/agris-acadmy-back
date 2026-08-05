<?php

namespace App\Filament\Resources\PartnerReportResource\Pages;

use App\Filament\Resources\PartnerReportResource;
use App\Models\PartnerReport;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartnerReport extends EditRecord
{
    protected static string $resource = PartnerReportResource::class;

    protected bool $justPublished = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var PartnerReport $report */
        $report = $this->record;
        $wasPublished = $report->status === 'published';
        $willPublish = ($data['status'] ?? 'draft') === 'published';

        $this->justPublished = $willPublish && ! $wasPublished;

        if ($this->justPublished) {
            $data['published_at'] = $report->published_at ?? now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->justPublished) {
            return;
        }

        /** @var PartnerReport $report */
        $report = $this->record->fresh(['partner']);

        if ($report?->partner) {
            NotificationService::create(
                $report->partner,
                'partner_report_ready',
                'New programme report',
                "\"{$report->title}\" is now available on your partner dashboard.",
                'partner_report',
                $report->id,
            );
        }
    }
}
