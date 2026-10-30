<?php

namespace App\Filament\Support;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Questions on a test (module test or topic test) — add one, bulk-add many,
 * edit, delete. Subclasses name the question model and its foreign key.
 */
abstract class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }

    /** @return class-string<Model> */
    abstract protected function questionModel(): string;

    abstract protected function foreignKey(): string;

    public function form(Form $form): Form
    {
        return $form->schema(CourseContentForms::question());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'multiple_choice' => 'MCQ',
                        'true_false' => 'True/False',
                        'short_answer' => 'Short Answer',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'multiple_choice' => 'primary',
                        'true_false' => 'success',
                        'short_answer' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('correct_answer')
                    ->label('Correct Answer'),
                Tables\Columns\TextColumn::make('points')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options([
                        'multiple_choice' => 'MCQ',
                        'true_false' => 'True/False',
                        'short_answer' => 'Short Answer',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add question')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['options'] = ($data['question_type'] ?? null) === 'multiple_choice'
                            ? CourseContentForms::repeaterToOptions($data['options'] ?? null)
                            : null;
                        if (! isset($data['sort_order']) || $data['sort_order'] === null || (int) $data['sort_order'] === 0) {
                            $data['sort_order'] = ($this->getOwnerRecord()->questions()->max('sort_order') ?? 0) + 1;
                        }
                        return $data;
                    }),
                $this->bulkCreateAction(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    // Load: stored {"A": ...} → repeater rows.
                    ->mutateRecordDataUsing(function (array $data, Model $record): array {
                        $data['options'] = CourseContentForms::optionsToRepeater($record->options);
                        return $data;
                    })
                    // Save: repeater rows → stored {"A": ...}.
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['options'] = ($data['question_type'] ?? null) === 'multiple_choice'
                            ? CourseContentForms::repeaterToOptions($data['options'] ?? null)
                            : null;
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    protected function bulkCreateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('bulk_create')
            ->label('Add multiple questions')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->form([
                Forms\Components\Repeater::make('questions')
                    ->label('Questions')
                    ->schema([
                        Forms\Components\Select::make('question_type')
                            ->label('Question Type')
                            ->options([
                                'multiple_choice' => 'MCQ (Multiple Choice)',
                                'true_false' => 'True/False',
                                'short_answer' => 'Short Answer',
                            ])
                            ->default('multiple_choice')
                            ->required()
                            ->reactive(),
                        Forms\Components\Textarea::make('question')
                            ->helperText('Leave blank to skip this row')
                            ->rows(2),
                        ...collect(['a', 'b', 'c', 'd'])->map(fn ($l) => Forms\Components\TextInput::make("option_{$l}")
                            ->label('Option ' . strtoupper($l))
                            ->visible(fn ($get) => $get('question_type') === 'multiple_choice'))->all(),
                        Forms\Components\Select::make('correct_answer')
                            ->label('Correct Answer')
                            ->options(function ($get) {
                                if ($get('question_type') === 'true_false') {
                                    return ['true' => 'True', 'false' => 'False'];
                                }
                                $result = [];
                                foreach (['A', 'B', 'C', 'D'] as $letter) {
                                    if ($value = $get('option_' . strtolower($letter))) {
                                        $result[$letter] = "{$letter}. {$value}";
                                    }
                                }
                                return $result;
                            })
                            ->required(fn ($get) => filled($get('question')))
                            ->reactive()
                            ->visible(fn ($get) => in_array($get('question_type'), ['multiple_choice', 'true_false'])),
                        Forms\Components\TextInput::make('correct_answer')
                            ->label('Correct Answer')
                            ->required(fn ($get) => filled($get('question')))
                            ->visible(fn ($get) => $get('question_type') === 'short_answer'),
                        Forms\Components\Textarea::make('explanation')
                            ->label('Explanation (optional)')
                            ->rows(1),
                        Forms\Components\TextInput::make('points')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                    ])
                    ->defaultItems(5)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'New question')
                    ->addActionLabel('Add another question')
                    ->reorderable()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                $owner = $this->getOwnerRecord();
                $sort = $owner->questions()->max('sort_order') ?? 0;
                $created = 0;
                $skipped = 0;
                $model = $this->questionModel();

                foreach ($data['questions'] as $q) {
                    if (blank($q['question'] ?? null)) {
                        continue; // blank row
                    }
                    $options = null;
                    if ($q['question_type'] === 'multiple_choice') {
                        $options = array_filter([
                            'A' => $q['option_a'] ?? null,
                            'B' => $q['option_b'] ?? null,
                            'C' => $q['option_c'] ?? null,
                            'D' => $q['option_d'] ?? null,
                        ], fn ($v) => $v !== null && $v !== '');
                        if (count($options) < 2) {
                            $skipped++;
                            continue;
                        }
                    }
                    $model::create([
                        $this->foreignKey() => $owner->id,
                        'question_type' => $q['question_type'],
                        'question' => $q['question'],
                        'options' => $options,
                        'correct_answer' => $q['correct_answer'],
                        'explanation' => $q['explanation'] ?? null,
                        'points' => $q['points'] ?? 1,
                        'sort_order' => ++$sort,
                    ]);
                    $created++;
                }

                Notification::make()
                    ->title("Added {$created} question(s)" . ($skipped ? " — skipped {$skipped} MCQ with fewer than 2 options" : ''))
                    ->success()
                    ->send();
            });
    }
}
