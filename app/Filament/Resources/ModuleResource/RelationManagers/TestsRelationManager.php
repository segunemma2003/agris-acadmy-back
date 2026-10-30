<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Filament\Support\CourseContentForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Module test tab on a module; each test opens to manage its questions. */
class TestsRelationManager extends RelationManager
{
    protected static string $relationship = 'test';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form->schema(CourseContentForms::test(80))->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('questions_count')->label('Questions')->counts('questions'),
                Tables\Columns\TextColumn::make('passing_score')->label('Pass mark')->suffix('%'),
                Tables\Columns\TextColumn::make('time_limit_minutes')->label('Time limit')->suffix(' min'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add module test')
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['course_id'] = $owner->course_id;
                        $data['tutor_id'] = $owner->course?->tutor_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('questions')
                    ->label('Questions')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn ($record) => \App\Filament\Resources\ModuleTestResource::getUrl('edit', ['record' => $record])),
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
