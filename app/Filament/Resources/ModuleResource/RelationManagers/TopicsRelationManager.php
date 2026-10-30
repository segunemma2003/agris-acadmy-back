<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Filament\Support\CourseContentForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Topics (lessons) tab on a module; each opens to its own topic tests and assignments. */
class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form->schema(CourseContentForms::topic())->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('content_type')->badge(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Duration')->suffix(' min'),
                Tables\Columns\TextColumn::make('test_count')->label('Tests')->counts('test'),
                Tables\Columns\IconColumn::make('is_free')->label('Free')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add topic')
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['tutor_id'] = $owner->course?->tutor_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Tests & assignments')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => \App\Filament\Resources\TopicResource::getUrl('edit', ['record' => $record])),
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
