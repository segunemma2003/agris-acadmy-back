<?php

namespace App\Filament\Support;

use Filament\Forms;

/**
 * Form fields for course content (modules, topics, tests, assignments,
 * questions) shared by the admin resources and the relation managers that
 * let admins drill down Course → Module → Topic → Test → Questions.
 * Fields mirror the tutor panel so both create identical records.
 */
class CourseContentForms
{
    public static function module(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('description')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Active (visible to learners)')
                ->default(true),
        ];
    }

    public static function topic(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('description')
                ->columnSpanFull(),
            Forms\Components\Select::make('content_type')
                ->options([
                    'video' => 'Video',
                    'text' => 'Text',
                    'audio' => 'Audio',
                    'interactive' => 'Interactive',
                ])
                ->default('video')
                ->required(),
            Forms\Components\TextInput::make('video_url')
                ->label('Video URL')
                ->url()
                ->maxLength(255),
            Forms\Components\Textarea::make('transcript')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('write_up')
                ->label('Write Up')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('duration_minutes')
                ->label('Duration (minutes)')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\Toggle::make('is_free')
                ->label('Free Preview')
                ->default(false),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ];
    }

    /** Module test and topic test share the same settings. */
    public static function test(int $defaultPassingScore): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('description')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('passing_score')
                ->label('Passing Score (%)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default($defaultPassingScore)
                ->required(),
            Forms\Components\TextInput::make('max_attempts')
                ->label('Max Attempts')
                ->numeric()
                ->minValue(1)
                ->helperText('Leave empty for unlimited retries'),
            Forms\Components\TextInput::make('time_limit_minutes')
                ->label('Time Limit (minutes)')
                ->numeric()
                ->default(60)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ];
    }

    /**
     * @param  array  $scopeFields  extra fields placed first, e.g. module/topic pickers
     */
    public static function assignment(array $scopeFields = []): array
    {
        return [
            ...$scopeFields,
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('description')
                ->required()
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('instructions')
                ->label('Instructions')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('max_score')
                ->label('Max Score')
                ->numeric()
                ->default(100)
                ->required(),
            Forms\Components\DateTimePicker::make('due_date')
                ->label('Due Date'),
            Forms\Components\TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ];
    }

    public static function question(): array
    {
        return [
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
            Forms\Components\FileUpload::make('image')
                ->label('Question Image (Optional)')
                ->image()
                ->imageEditor()
                ->imagePreviewHeight('200')
                ->directory('test-questions')
                ->visibility('public')
                ->columnSpanFull(),
            Forms\Components\Repeater::make('options')
                ->label('Options')
                ->schema([
                    Forms\Components\TextInput::make('option')
                        ->label('Option')
                        ->required(),
                ])
                ->defaultItems(4)
                ->maxItems(6)
                ->visible(fn ($get) => $get('question_type') === 'multiple_choice')
                ->required(fn ($get) => $get('question_type') === 'multiple_choice')
                ->columnSpanFull(),
            Forms\Components\Select::make('correct_answer')
                ->label('Correct Answer')
                ->options(function ($get) {
                    if ($get('question_type') === 'true_false') {
                        return ['true' => 'True', 'false' => 'False'];
                    }
                    $result = [];
                    foreach (array_values($get('options') ?? []) as $index => $option) {
                        if (isset($option['option'])) {
                            $letter = chr(65 + $index);
                            $result[$letter] = $letter . '. ' . $option['option'];
                        }
                    }
                    return $result;
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
        ];
    }

    /** Stored shape {"A": "...", "B": "..."} → repeater rows [{option: "..."}]. */
    public static function optionsToRepeater(?array $options): ?array
    {
        if (! is_array($options)) {
            return $options;
        }
        return array_map(fn ($value) => ['option' => $value], array_values($options));
    }

    /** Repeater rows [{option: "..."}] → stored shape {"A": "...", "B": "..."}. */
    public static function repeaterToOptions($rows): ?array
    {
        if (! is_array($rows)) {
            return null;
        }
        $options = [];
        foreach (array_values($rows) as $index => $row) {
            $value = is_array($row) ? ($row['option'] ?? null) : $row;
            if ($value !== null && $value !== '') {
                $options[chr(65 + $index)] = $value;
            }
        }
        return $options ?: null;
    }
}
