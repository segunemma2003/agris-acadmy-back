<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Modules tab on the admin course page — admins add, edit, reorder and
 * delete a course's modules here (same fields tutors fill in).
 */
class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(fn () => ($this->getOwnerRecord()->modules()->max('sort_order') ?? 0) + 1)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active (visible to learners)')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('topics_count')
                    ->label('Topics')
                    ->counts('topics'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add module')
                    // Keep the course's tutor as the module's owner so it stays
                    // visible/editable in the tutor panel.
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tutor_id'] = $this->getOwnerRecord()->tutor_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
