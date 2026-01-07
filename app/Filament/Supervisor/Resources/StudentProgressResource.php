<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Supervisor\Resources\StudentProgressResource\Pages;
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
                            ->relationship('user', 'name', fn ($query) => $query->where('role', 'student'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
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
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('course', fn ($q) => $q->where('tutor_id', Auth::id())))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
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
                    ->relationship('course', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
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
}

