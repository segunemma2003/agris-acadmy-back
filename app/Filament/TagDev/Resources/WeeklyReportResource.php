<?php

namespace App\Filament\TagDev\Resources;

use App\Filament\TagDev\Resources\WeeklyReportResource\Pages;
use App\Models\WeeklyReport;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WeeklyReportResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Weekly Reports';
    
    protected static ?int $navigationSort = 1;

    public static function infolist(Infolist $infolist): Infolist
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
                        Infolists\Components\TextEntry::make('advice')
                            ->label('Advice/Recommendations')
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
                Infolists\Components\Section::make('Admin Feedback')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_feedback')
                            ->label('Admin Feedback')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->admin_feedback)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable(),
                Tables\Columns\TextColumn::make('report_week_start')
                    ->label('Week Start')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_week_end')
                    ->label('Week End')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_students')
                    ->label('Total Students')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_students')
                    ->label('Active Students')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'reviewed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                    ]),
                Tables\Filters\SelectFilter::make('tutor_id')
                    ->label('Tutor')
                    ->relationship('tutor', 'name')
                    ->searchable(),
            ])
            ->actions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyReports::route('/'),
            // No create/edit/view pages - read-only
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Read-only
    }

    public static function canEdit($record): bool
    {
        return false; // Read-only
    }

    public static function canDelete($record): bool
    {
        return false; // Read-only
    }
}
