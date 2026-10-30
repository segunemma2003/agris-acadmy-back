<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Filament\Support\CourseContentForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Assignments tab on a module. */
class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form->schema(CourseContentForms::assignment([
            Forms\Components\Select::make('topic_id')
                ->label('Topic (optional)')
                ->options(fn () => $this->getOwnerRecord()->topics()->orderBy('sort_order')->pluck('title', 'id'))
                ->searchable()
                ->columnSpanFull(),
        ]))->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('topic.title')->label('Topic')->placeholder('Whole module'),
                Tables\Columns\TextColumn::make('submissions_count')->label('Submissions')->counts('submissions'),
                Tables\Columns\TextColumn::make('due_date')->dateTime()->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add assignment')
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['course_id'] = $owner->course_id;
                        $data['tutor_id'] = $owner->course?->tutor_id;
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
