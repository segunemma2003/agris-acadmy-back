<?php

namespace App\Filament\Tutor\Resources\TopicTestResource\RelationManagers;

use App\Models\TopicTestQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Form $form): Form
    {
        return $form
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
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state !== 'multiple_choice') {
                            $set('options', null);
                        }
                    }),
                Forms\Components\Textarea::make('question')
                    ->label('Question')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('options')
                    ->label('Options')
                    ->schema([
                        Forms\Components\TextInput::make('option')
                            ->label('Option')
                            ->required(),
                    ])
                    ->defaultItems(4)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice')
                    ->required(fn ($get) => $get('question_type') === 'multiple_choice')
                    ->columnSpanFull(),
                Forms\Components\Select::make('correct_answer')
                    ->label('Correct Answer')
                    ->options(function ($get) {
                        if ($get('question_type') === 'true_false') {
                            return [
                                'true' => 'True',
                                'false' => 'False',
                            ];
                        }
                        if ($get('question_type') === 'multiple_choice') {
                            $options = $get('options') ?? [];
                            $result = [];
                            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                            foreach ($options as $index => $option) {
                                if (isset($option['option'])) {
                                    $result[$letters[$index]] = $letters[$index] . '. ' . $option['option'];
                                }
                            }
                            return $result;
                        }
                        return [];
                    })
                    ->required()
                    ->reactive()
                    ->visible(fn ($get) => in_array($get('question_type'), ['multiple_choice', 'true_false'])),
                Forms\Components\TextInput::make('correct_answer')
                    ->label('Correct Answer')
                    ->required()
                    ->visible(fn ($get) => $get('question_type') === 'short_answer')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('explanation')
                    ->label('Explanation (optional)')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('points')
                    ->label('Points')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),
            ]);
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
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'multiple_choice' => 'MCQ',
                        'true_false' => 'True/False',
                        'short_answer' => 'Short Answer',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'multiple_choice' => 'primary',
                        'true_false' => 'success',
                        'short_answer' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('correct_answer')
                    ->label('Correct Answer'),
                Tables\Columns\TextColumn::make('points')
                    ->label('Points')
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
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert repeater options to array format
                        if (isset($data['options']) && is_array($data['options'])) {
                            $optionsArray = [];
                            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                            foreach ($data['options'] as $index => $option) {
                                if (isset($option['option'])) {
                                    $optionsArray[$letters[$index]] = $option['option'];
                                }
                            }
                            $data['options'] = $optionsArray;
                        }
                        // Set sort_order if not provided
                        if (!isset($data['sort_order']) || $data['sort_order'] === null) {
                            $data['sort_order'] = $this->getOwnerRecord()->questions()->max('sort_order') + 1 ?? 0;
                        }
                        return $data;
                    }),
                Tables\Actions\Action::make('bulk_create')
                    ->label('Bulk Create Questions (Add Multiple)')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->size('lg')
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
                                    ->label('Question')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\TextInput::make('option_a')
                                    ->label('Option A')
                                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice'),
                                Forms\Components\TextInput::make('option_b')
                                    ->label('Option B')
                                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice'),
                                Forms\Components\TextInput::make('option_c')
                                    ->label('Option C')
                                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice'),
                                Forms\Components\TextInput::make('option_d')
                                    ->label('Option D')
                                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice'),
                                Forms\Components\Select::make('correct_answer')
                                    ->label('Correct Answer')
                                    ->options(function ($get) {
                                        if ($get('question_type') === 'true_false') {
                                            return [
                                                'true' => 'True',
                                                'false' => 'False',
                                            ];
                                        }
                                        if ($get('question_type') === 'multiple_choice') {
                                            $result = [];
                                            $options = ['A' => $get('option_a'), 'B' => $get('option_b'), 'C' => $get('option_c'), 'D' => $get('option_d')];
                                            foreach ($options as $letter => $value) {
                                                if ($value) {
                                                    $result[$letter] = $letter . '. ' . $value;
                                                }
                                            }
                                            return $result;
                                        }
                                        return [];
                                    })
                                    ->required()
                                    ->reactive()
                                    ->visible(fn ($get) => in_array($get('question_type'), ['multiple_choice', 'true_false'])),
                                Forms\Components\TextInput::make('correct_answer')
                                    ->label('Correct Answer')
                                    ->required()
                                    ->visible(fn ($get) => $get('question_type') === 'short_answer'),
                                Forms\Components\Textarea::make('explanation')
                                    ->label('Explanation (optional)')
                                    ->rows(1),
                                Forms\Components\TextInput::make('points')
                                    ->label('Points')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                            ])
                            ->defaultItems(10)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'New Question')
                            ->addActionLabel('Add Another Question')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        $ownerRecord = $this->getOwnerRecord();
                        $maxSortOrder = $ownerRecord->questions()->max('sort_order') ?? 0;
                        $created = 0;

                        foreach ($data['questions'] as $index => $questionData) {
                            // Convert options to array format for multiple choice
                            $optionsArray = null;
                            if ($questionData['question_type'] === 'multiple_choice') {
                                $optionsArray = [];
                                if (!empty($questionData['option_a'])) $optionsArray['A'] = $questionData['option_a'];
                                if (!empty($questionData['option_b'])) $optionsArray['B'] = $questionData['option_b'];
                                if (!empty($questionData['option_c'])) $optionsArray['C'] = $questionData['option_c'];
                                if (!empty($questionData['option_d'])) $optionsArray['D'] = $questionData['option_d'];
                                // Only create if at least 2 options are provided
                                if (count($optionsArray) < 2) {
                                    continue;
                                }
                            }

                            TopicTestQuestion::create([
                                'topic_test_id' => $ownerRecord->id,
                                'question_type' => $questionData['question_type'],
                                'question' => $questionData['question'],
                                'options' => $optionsArray,
                                'correct_answer' => $questionData['correct_answer'],
                                'explanation' => $questionData['explanation'] ?? null,
                                'points' => $questionData['points'] ?? 1,
                                'sort_order' => $maxSortOrder + $index + 1,
                            ]);
                            $created++;
                        }

                        Notification::make()
                            ->title('Successfully created ' . $created . ' question(s)')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, Model $record): array {
                        // Convert options array to repeater format
                        if (isset($record->options) && is_array($record->options)) {
                            $optionsRepeater = [];
                            foreach ($record->options as $key => $value) {
                                $optionsRepeater[] = ['option' => $value];
                            }
                            $data['options'] = $optionsRepeater;
                        }
                        return $data;
                    })
                    ->mutateRecordDataUsing(function (array $data, Model $record): array {
                        // Convert options array to repeater format
                        if (isset($record->options) && is_array($record->options)) {
                            $optionsRepeater = [];
                            foreach ($record->options as $key => $value) {
                                $optionsRepeater[] = ['option' => $value];
                            }
                            $data['options'] = $optionsRepeater;
                        }
                        return $data;
                    }),
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




