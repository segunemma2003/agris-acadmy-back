<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\AssignmentSubmissionResource\Pages;
use App\Models\AssignmentSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AssignmentSubmissionResource extends Resource
{
    protected static ?string $model = AssignmentSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Information')
                    ->schema([
                        Forms\Components\Select::make('assignment_id')
                            ->label('Assignment')
                            ->relationship('assignment', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name', fn ($query) => $query->where('role', 'student'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),
                        Forms\Components\TextInput::make('assignment_max_score_display')
                            ->label('Maximum Score')
                            ->default(fn ($record) => $record && $record->assignment ? $record->assignment->max_score . ' points' : 'N/A')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('submitted_at_display')
                            ->label('Submitted At')
                            ->default(fn ($record) => $record ? $record->submitted_at?->format('F j, Y g:i A') : 'N/A')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
                Forms\Components\Section::make('Submission Content')
                    ->schema([
                        Forms\Components\RichEditor::make('submission_content')
                            ->label('Submission Content')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('file_info')
                            ->label('Attached File')
                            ->default(function ($record) {
                                if ($record && $record->file_path) {
                                    return $record->file_name ?? basename($record->file_path);
                                }
                                return 'No file attached';
                            })
                            ->disabled()
                            ->dehydrated(false)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('download')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(fn ($record) => $record && $record->file_path ? asset('storage/' . $record->file_path) : null)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => $record && $record->file_path)
                            )
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Grading')
                    ->schema([
                        Forms\Components\TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn ($record) => $record && $record->assignment ? ' / ' . $record->assignment->max_score : '')
                            ->helperText(fn ($record) => $record && $record->assignment 
                                ? 'Maximum score: ' . $record->assignment->max_score . ' points'
                                : 'Enter the score for this submission')
                            ->required(fn ($get) => $get('status') === 'graded')
                            ->reactive(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'submitted' => 'Submitted (Pending Review)',
                                'graded' => 'Graded',
                                'returned' => 'Returned',
                            ])
                            ->default('submitted')
                            ->required()
                            ->reactive()
                            ->helperText('Select "Graded" when you have completed scoring'),
                        Forms\Components\RichEditor::make('feedback')
                            ->label('Feedback to Student')
                            ->helperText('Provide feedback on the submission')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['assignment', 'user'])
                ->whereHas('assignment', fn ($q) => $q->where('tutor_id', Auth::id())))
            ->columns([
                Tables\Columns\TextColumn::make('assignment.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'submitted' => 'Submitted (Pending)',
                        'graded' => 'Graded',
                        'returned' => 'Returned',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'warning',
                        'graded' => 'success',
                        'returned' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($record) => $record->score !== null 
                        ? $record->score . ' / ' . ($record->assignment->max_score ?? 100)
                        : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('graded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_id')
                    ->label('Assignment')
                    ->relationship('assignment', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted (Pending)',
                        'graded' => 'Graded',
                        'returned' => 'Returned',
                    ]),
                Tables\Filters\TernaryFilter::make('score')
                    ->label('Has Score')
                    ->placeholder('All submissions')
                    ->trueLabel('Scored')
                    ->falseLabel('Not Scored')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('score'),
                        false: fn ($query) => $query->whereNull('score'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignmentSubmissions::route('/'),
            'edit' => Pages\EditAssignmentSubmission::route('/{record}/edit'),
        ];
    }
}

