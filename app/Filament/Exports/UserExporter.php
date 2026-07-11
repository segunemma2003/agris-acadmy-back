<?php

namespace App\Filament\Exports;

use App\Models\StudentProgress;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * MEL (Monitoring, Evaluation & Learning) export of learner engagement metrics
 * for the admin panel — the same figures shown on the learner dashboard.
 */
class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('state'),
            ExportColumn::make('lga'),
            ExportColumn::make('created_at')->label('Registered At'),
            ExportColumn::make('last_active_date')->label('Last Active Date'),
            ExportColumn::make('current_streak')->label('Current Streak (Days)'),
            ExportColumn::make('longest_streak')->label('Longest Streak (Days)'),
            ExportColumn::make('total_courses')
                ->label('Total Courses Enrolled')
                ->state(fn (User $record) => $record->enrollments()->count()),
            ExportColumn::make('completed_courses')
                ->label('Courses Completed')
                ->state(fn (User $record) => $record->enrollments()->where('status', 'completed')->count()),
            ExportColumn::make('certificates_acquired')
                ->state(fn (User $record) => $record->certificates()->count()),
            ExportColumn::make('total_hours_on_platform')
                ->label('Total Hours on Platform')
                ->state(fn (User $record) => round(
                    StudentProgress::where('user_id', $record->id)->sum('watch_time_seconds') / 3600,
                    2
                )),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your learner engagement export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
