<?php

namespace App\Filament\Resources\TopicResource\RelationManagers;

use App\Filament\Support\CourseContentForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Assignments / classwork tab on a topic. */
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
        return $form->schema(CourseContentForms::assignment())->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
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
                        $data['module_id'] = $owner->module_id;
                        $data['course_id'] = $owner->module?->course_id;
                        $data['tutor_id'] = $owner->module?->course?->tutor_id;
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
