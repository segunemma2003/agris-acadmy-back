<?php

namespace App\Filament\Resources\WeeklyReportResource\Pages;

use App\Filament\Resources\WeeklyReportResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWeeklyReport extends ViewRecord
{
    protected static string $resource = WeeklyReportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Report Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('tutor.name')
                            ->label('Tutor'),
                        Infolists\Components\TextEntry::make('course.title')
                            ->label('Course'),
                        Infolists\Components\TextEntry::make('report_week_start')
                            ->label('Week Start')
                            ->date(),
                        Infolists\Components\TextEntry::make('report_week_end')
                            ->label('Week End')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'submitted' => 'info',
                                'reviewed' => 'success',
                                default => 'gray',
                            }),
                    ])->columns(2),
                Infolists\Components\Section::make('Report Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('weekly_plan')
                            ->label('Weekly Plan')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('achievements')
                            ->label('Achievements')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('activities_completed')
                            ->label('Activities Completed')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('challenges')
                            ->label('Challenges')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('next_week_plans')
                            ->label('Next Week Plans')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_students')
                            ->label('Total Students'),
                        Infolists\Components\TextEntry::make('active_students')
                            ->label('Active Students'),
                        Infolists\Components\TextEntry::make('completed_assignments')
                            ->label('Completed Assignments'),
                    ])->columns(3),
                Infolists\Components\Section::make('Media')
                    ->schema([
                        Infolists\Components\ViewEntry::make('images')
                            ->label('Images')
                            ->view('filament.forms.components.weekly-report-images')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('video_links')
                            ->label('Video Links')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return 'No video links';
                                return collect($state)->map(fn($link) => '<a href="' . ($link['url'] ?? $link) . '" target="_blank" class="text-blue-600 hover:underline">' . ($link['url'] ?? $link) . '</a>')->join('<br>');
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->images) || !empty($record->video_links)),
                Infolists\Components\Section::make('Advice')
                    ->schema([
                        Infolists\Components\TextEntry::make('advice')
                            ->label('Advice/Recommendations')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->advice)),
                Infolists\Components\Section::make('Admin Feedback')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_feedback')
                            ->label('Feedback')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->admin_feedback)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

