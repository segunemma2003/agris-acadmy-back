<?php

namespace App\Filament\Facilitator\Resources\ModuleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TestRelationManager extends RelationManager
{
    protected static string $relationship = 'test';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('passing_score')
                    ->label('Passing Score (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(70)
                    ->required(),
                Forms\Components\TextInput::make('time_limit_minutes')
                    ->label('Time Limit (minutes)')
                    ->numeric()
                    ->default(60)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Passing Score')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_limit_minutes')
                    ->label('Time Limit')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Automatically set module_id and course_id from the owner record
                        $data['module_id'] = $this->getOwnerRecord()->id;
                        $data['course_id'] = $this->getOwnerRecord()->course_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('questions')
                    ->label('Manage Questions')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn ($record) => \App\Filament\Facilitator\Resources\ModuleTestResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
