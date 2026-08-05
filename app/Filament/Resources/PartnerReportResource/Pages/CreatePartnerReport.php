<?php

namespace App\Filament\Resources\PartnerReportResource\Pages;

use App\Filament\Resources\PartnerReportResource;
use App\Models\PartnerReport;
use App\Services\NotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePartnerReport extends CreateRecord
{
    protected static string $resource = PartnerReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        if (($data['status'] ?? 'draft') === 'published') {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var PartnerReport $report */
        $report = $this->record;

        if ($report->status === 'published' && $report->partner) {
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
