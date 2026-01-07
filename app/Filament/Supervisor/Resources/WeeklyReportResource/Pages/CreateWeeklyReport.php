<?php

namespace App\Filament\Supervisor\Resources\WeeklyReportResource\Pages;

use App\Filament\Supervisor\Resources\WeeklyReportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWeeklyReport extends CreateRecord
{
    protected static string $resource = WeeklyReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tutor_id'] = Auth::id();
        $data['status'] = 'draft';
        return $data;
    }
}
