<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\StudentProgressResource\Pages;
use App\Models\StudentProgress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StudentProgressResource extends Resource
{
    protected static ?string $model = StudentProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Progress Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name', function ($query) {
                                $facilitatorLocation = Auth::user()->location;
                                return $query->where('role', 'student')
                                             ->whereNotNull('location')
                                             ->where('location', '!=', '')
                                             ->where('location', $facilitatorLocation);
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', function ($query) {
                                $facilitatorLocation = Auth::user()->location;
                                return $query->whereHas('enrollments', function ($eq) use ($facilitatorLocation) {
                                    $eq->whereHas('user', function ($uq) use ($facilitatorLocation) {
                                        $uq->where('role', 'student')
                                           ->whereNotNull('location')
                                           ->where('location', '!=', '')
                                           ->where('location', $facilitatorLocation);
                                    });
                                });
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('topic_id')
                            ->label('Topic')
                            ->relationship('topic', 'title')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_completed')
                            ->default(false),
                        Forms\Components\TextInput::make('completion_percentage')
                            ->label('Completion %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                        Forms\Components\TextInput::make('watch_time_seconds')
                            ->label('Watch Time (seconds)')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $facilitatorLocation = Auth::user()->location;
        
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('user', function ($uq) use ($facilitatorLocation) {
                $uq->where('role', 'student')
                   ->whereNotNull('location')
                   ->where('location', '!=', '')
                   ->where('location', $facilitatorLocation);
            }))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.location')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('topic.title')
                    ->label('Topic')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('Progress %')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title', function ($query) use ($facilitatorLocation) {
                        // Only show courses that have enrollments from students in facilitator's location
                        $query->whereHas('enrollments', function ($eq) use ($facilitatorLocation) {
                            $eq->whereHas('user', function ($uq) use ($facilitatorLocation) {
                                $uq->where('role', 'student')
                                   ->whereNotNull('location')
                                   ->where('location', '!=', '')
                                   ->where('location', $facilitatorLocation);
                            });
                        });
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('Completed'),
            ])
            ->defaultSort('last_accessed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProgress::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        if (!$record->user || $record->user->role !== 'student') {
            return false;
        }
        
        $facilitatorLocation = Auth::user()->location;
        
        // Facilitators can only see students with a location that matches theirs
        // Students without location are hidden from facilitators
        if (empty($facilitatorLocation) || empty($record->user->location)) {
            return false;
        }
        
        return $record->user->location === $facilitatorLocation;
    }
}