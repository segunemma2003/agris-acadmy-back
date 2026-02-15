<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\WeeklyReportResource\Pages;
use App\Models\WeeklyReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class WeeklyReportResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Weekly Reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $facilitator = Auth::user();
        $facilitatorLocation = $facilitator?->location;

        return $form
            ->schema([
                // Ensure every report created/edited in this panel is tied to the logged-in facilitator
                Forms\Components\Hidden::make('facilitator_id')
                    ->default(fn () => Auth::id())
                    ->dehydrated(true),

                Forms\Components\Section::make('Report Period')
                    ->schema([
                        Forms\Components\DatePicker::make('report_week_start')
                            ->label('Week Start Date')
                            ->required()
                            ->default(Carbon::now()->startOfWeek())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('report_week_end', Carbon::parse($state)->endOfWeek());
                                }
                            }),
                        Forms\Components\DatePicker::make('report_week_end')
                            ->label('Week End Date')
                            ->required()
                            ->default(Carbon::now()->endOfWeek()),
                        Forms\Components\Select::make('course_id')
                            ->label('Course (Optional)')
                            ->relationship('course', 'title', function ($query) use ($facilitatorLocation) {
                                // Keep your original logic here (courses by tutors in same location)
                                // This does NOT control submit/download anymore.
                                if ($facilitatorLocation) {
                                    $query->whereHas('tutor', function ($q) use ($facilitatorLocation) {
                                        $q->where('location', $facilitatorLocation);
                                    });
                                }
                            })
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Weekly Activities')
                    ->schema([
                        Forms\Components\RichEditor::make('weekly_plan')
                            ->label('Weekly Plan')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('achievements')
                            ->label('Achievements')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('activities_completed')
                            ->label('Activities Completed')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('challenges')
                            ->label('Challenges Faced')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('next_week_plans')
                            ->label('Plans for Next Week')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('total_students')
                            ->label('Total Number of Students')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('active_students')
                            ->label('Active Students')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('completed_assignments')
                            ->label('Completed Assignments')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Media & Links')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Upload Images')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->multiple()
                            ->directory('weekly-reports')
                            ->preserveFilenames()
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('video_links')
                            ->label('Video Links (Optional)')
                            ->schema([
                                Forms\Components\TextInput::make('url')
                                    ->label('Video URL')
                                    ->url()
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Advice')
                    ->schema([
                        Forms\Components\RichEditor::make('advice')
                            ->label('Advice/Recommendations (Optional)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $facilitator = Auth::user();

                if (!$facilitator) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                // PRIMARY: facilitator-based ownership
                $query->where('facilitator_id', $facilitator->id);

                // SECONDARY (optional): facilitator location match enforcement
                // This only matters if you want to prevent a facilitator from seeing their own reports
                // after their location changes. If you don't want that, you can remove this block.
                if ($facilitator->location) {
                    $query->whereHas('facilitator', function ($q) use ($facilitator) {
                        $q->where('location', $facilitator->location);
                    });
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('facilitator.name')
                    ->label('Facilitator')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('report_week_start')
                    ->label('Week Start')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('report_week_end')
                    ->label('Week End')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable(),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
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
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn ($record) => static::canEdit($record))
                    ->action(function ($record) {
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

                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ($record->status === 'draft') && static::canEdit($record))
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'submitted',
                            'submitted_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Weekly report submitted')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyReports::route('/'),
            'create' => Pages\CreateWeeklyReport::route('/create'),
            'view' => Pages\ViewWeeklyReport::route('/{record}'),
            'edit' => Pages\EditWeeklyReport::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'facilitator';
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        $facilitator = Auth::user();

        if (!$facilitator) {
            return false;
        }

        // Must belong to facilitator
        if ((int) ($record->facilitator_id ?? 0) !== (int) $facilitator->id) {
            return false;
        }

        // Optional: also enforce location match (strict)
        if (!$facilitator->location) {
            return false;
        }

        // Check facilitator location matches (via relationship if available)
        if ($record->facilitator && $record->facilitator->location) {
            return $record->facilitator->location === $facilitator->location;
        }

        // If facilitator relationship isn't loaded, still allow based on current facilitator location being set.
        return true;
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }
}
