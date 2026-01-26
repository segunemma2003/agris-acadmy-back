<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\AssignmentSubmissionResource\Pages;
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
                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\Select::make('assignment_id')
                            ->label('Assignment')
                            ->relationship('assignment', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name', fn ($query) => $query->where('role', 'student'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\RichEditor::make('submission_content')
                            ->label('Submission Content')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'graded' => 'Graded',
                                'returned' => 'Returned',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->maxValue(100),
                        Forms\Components\RichEditor::make('feedback')
                            ->label('Feedback')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('assignment', fn ($q) => $q->where('tutor_id', Auth::id())))
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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'graded' => 'success',
                        'returned' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('score')
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
                Tables\Filters\SelectFilter::make('status'),
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

    public static function canViewAny(): bool
    {
        return false; // Facilitators can only view Weekly Reports
    }
}