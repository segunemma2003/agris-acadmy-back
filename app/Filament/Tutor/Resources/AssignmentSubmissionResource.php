<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\AssignmentSubmissionResource\Pages;
use App\Models\AssignmentSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentSubmissionResource extends Resource
{
    protected static ?string $model = AssignmentSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Assignment Submissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\Select::make('assignment_id')
                            ->label('Assignment')
                            ->relationship('assignment', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('submission_content')
                            ->label('Submission Content')
                            ->rows(5)
                            ->columnSpanFull()
                            ->disabled(),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Submitted File')
                            ->disabled()
                            ->downloadable()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Grading')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'graded' => 'Graded',
                                'returned' => 'Returned',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn ($get) => \App\Models\Assignment::find($get('assignment_id'))?->max_score ?? 100)
                            ->suffix(fn ($get) => ' / ' . (\App\Models\Assignment::find($get('assignment_id'))?->max_score ?? 100)),
                        Forms\Components\RichEditor::make('feedback')
                            ->label('Feedback')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('assignment', fn ($q) => $q->where('tutor_id', auth()->id())))
            ->columns([
                Tables\Columns\TextColumn::make('assignment.title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'graded' => 'success',
                        'returned' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state, $record) => $state ? $state . ' / ' . $record->assignment->max_score : '-')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
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
                    ->relationship('assignment', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\Action::make('grade')
                    ->label('Grade')
                    ->icon('heroicon-o-check-circle')
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn ($record) => $record->assignment->max_score),
                        Forms\Components\RichEditor::make('feedback')
                            ->label('Feedback'),
                    ])
                    ->action(function (AssignmentSubmission $record, array $data) {
                        $record->update([
                            'score' => $data['score'],
                            'feedback' => $data['feedback'],
                            'status' => 'graded',
                            'graded_at' => now(),
                        ]);
                    })
                    ->visible(fn ($record) => $record->status === 'pending'),
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

