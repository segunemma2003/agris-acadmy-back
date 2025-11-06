<?php

namespace App\Filament\Tutor\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Student')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'student'))
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('enrollment_code')
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('enrollment_code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_progress')
                    ->label('View Progress')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record) => \App\Filament\Tutor\Resources\StudentProgressResource::getUrl('index', ['user_id' => $record->user_id, 'course_id' => $record->course_id])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }
}

