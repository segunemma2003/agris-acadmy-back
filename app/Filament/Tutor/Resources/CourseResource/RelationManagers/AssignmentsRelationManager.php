<?php

namespace App\Filament\Tutor\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('module_id')
                    ->label('Module (Optional)')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('instructions')
                    ->label('Instructions')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('max_score')
                    ->label('Maximum Score')
                    ->numeric()
                    ->default(100)
                    ->required(),
                Forms\Components\DateTimePicker::make('due_date')
                    ->label('Due Date')
                    ->native(false),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('module.title')
                    ->label('Module')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('max_score')
                    ->label('Max Score')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Filament\Tutor\Resources\AssignmentSubmissionResource::getUrl('index', ['assignment_id' => $record->id])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}

