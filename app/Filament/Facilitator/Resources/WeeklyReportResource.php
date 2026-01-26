<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\WeeklyReportResource\Pages;
use App\Models\WeeklyReport;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        $facilitatorLocation = $facilitator->location;

        return $form
            ->schema([
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
                                // Only show courses from tutors in the same location
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
        $facilitator = Auth::user();
        $facilitatorLocation = $facilitator->location;

        return $table
            ->modifyQueryUsing(function ($query) use ($facilitatorLocation) {
                // Filter reports by facilitator's location
                // Show only reports from tutors who have the same location
                if ($facilitatorLocation) {
                    $query->whereHas('tutor', function ($q) use ($facilitatorLocation) {
                        $q->where('location', $facilitatorLocation);
                    });
                } else {
                    // If facilitator has no location, show no reports
                    $query->whereRaw('1 = 0');
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
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
        return true; // Facilitators can create reports
    }

    public static function canEdit($record): bool
    {
        // Facilitators can only edit their own reports or reports from their location
        $facilitator = Auth::user();
        $facilitatorLocation = $facilitator->location;
        
        if (!$facilitatorLocation) {
            return false;
        }
        
        // Check if the report's tutor has the same location
        return $record->tutor && $record->tutor->location === $facilitatorLocation;
    }

    public static function canDelete($record): bool
    {
        // Same as canEdit
        return static::canEdit($record);
    }
}
