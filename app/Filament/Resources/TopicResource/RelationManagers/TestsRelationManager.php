<?php

namespace App\Filament\Resources\TopicResource\RelationManagers;

use App\Filament\Support\CourseContentForms;
use App\Filament\Support\PanelAwareContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Topic test tab on a topic; each test opens to manage its questions. */
class TestsRelationManager extends RelationManager
{
    use PanelAwareContent;

    protected static string $relationship = 'test';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form->schema(CourseContentForms::test(70))->columns(2);
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
                    ->label('Add topic test')
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['module_id'] = $owner->module_id;
                        $data['course_id'] = $owner->module?->course_id;
                        $data['tutor_id'] = $this->contentTutorId($owner->module?->course?->tutor_id);
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('questions')
                    ->label('Questions')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn ($record) => $this->contentUrl('TopicTestResource', $record)),
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
