<?php

namespace App\Filament\Facilitator\Resources\WeeklyReportResource\Pages;

use App\Filament\Facilitator\Resources\WeeklyReportResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyReport extends EditRecord
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('Submit Report')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () =>
                    ($this->record->status ?? null) === 'draft'
                    && WeeklyReportResource::canEdit($this->record)
                )
                ->action(function (): void {
                    // Extra safety: only submit if facilitator is allowed
                    if (!WeeklyReportResource::canEdit($this->record)) {
                        Notification::make()
                            ->title('You are not allowed to submit this report.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (($this->record->status ?? null) !== 'draft') {
                        Notification::make()
                            ->title('This report has already been submitted.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $this->record->update([
                        'status' => 'submitted',
                        'submitted_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Weekly report submitted successfully')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => WeeklyReportResource::canEdit($this->record))
                ->action(function () {
                    $record = $this->record;

                    // Extra safety
                    if (!WeeklyReportResource::canEdit($record)) {
                        Notification::make()
                            ->title('You are not allowed to download this report.')
                            ->danger()
                            ->send();
                        return null;
                    }

                    $filename = 'weekly-report-' . $record->id . '.csv';

                    return response()->streamDownload(function () use ($record) {
                        $out = fopen('php://output', 'w');

                        fputcsv($out, ['Field', 'Value']);

                        $rows = [
                            'Facilitator' => optional($record->facilitator)->name,
                            'Week Start' => optional($record->report_week_start)?->toDateString(),
                            'Week End' => optional($record->report_week_end)?->toDateString(),
                            'Course' => optional($record->course)->title,
                            'Weekly Plan' => strip_tags((string) ($record->weekly_plan ?? '')),
                            'Achievements' => strip_tags((string) ($record->achievements ?? '')),
                            'Activities Completed' => strip_tags((string) ($record->activities_completed ?? '')),
                            'Challenges' => strip_tags((string) ($record->challenges ?? '')),
                            'Next Week Plans' => strip_tags((string) ($record->next_week_plans ?? '')),
                            'Advice' => strip_tags((string) ($record->advice ?? '')),
                            'Total Students' => $record->total_students ?? null,
                            'Active Students' => $record->active_students ?? null,
                            'Completed Assignments' => $record->completed_assignments ?? null,
                            'Status' => $record->status ?? null,
                            'Submitted At' => optional($record->submitted_at)?->toDateTimeString(),
                            'Created At' => optional($record->created_at)?->toDateTimeString(),
                        ];

                        foreach ($rows as $field => $value) {
                            fputcsv($out, [$field, $value]);
                        }

                        fclose($out);
                    }, $filename, ['Content-Type' => 'text/csv']);
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => WeeklyReportResource::canEdit($this->record)),
        ];
    }
}
