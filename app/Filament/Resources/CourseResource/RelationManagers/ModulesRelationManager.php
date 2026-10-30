<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Filament\Support\CourseContentForms;
use App\Filament\Support\PanelAwareContent;
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
    use PanelAwareContent;

    protected static string $relationship = 'modules';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...collect(CourseContentForms::module())->map(
                    fn ($field) => $field->getName() === 'sort_order'
                        ? $field->default(fn () => ($this->getOwnerRecord()->modules()->max('sort_order') ?? 0) + 1)
                        : $field
                )->all(),
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
                Tables\Columns\TextColumn::make('test_count')
                    ->label('Tests')
                    ->counts('test'),
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
                        $data['tutor_id'] = $this->contentTutorId($this->getOwnerRecord()->tutor_id);
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Topics, tests & assignments')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $this->contentUrl('ModuleResource', $record)),
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
