<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\StudentProgressResource\Pages;
use App\Models\StudentProgress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentProgressResource extends Resource
{
    protected static ?string $model = StudentProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Student Progress';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Progress Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name', fn (Builder $query) => $query->where('role', 'student'))
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('topic_id')
                            ->label('Topic')
                            ->relationship('topic', 'title')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_completed')
                            ->label('Completed')
                            ->required(),
                        Forms\Components\TextInput::make('progress_percentage')
                            ->label('Progress %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('tutor_id', auth()->id())))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('topic.title')
                    ->label('Topic')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Student')
                    ->relationship('user', 'name', fn (Builder $query) => $query->where('role', 'student'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_completed'),
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

