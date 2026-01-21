<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\TestAttemptResource\Pages;
use App\Models\TestAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TestAttemptResource extends Resource
{
    protected static ?string $model = TestAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Module Test Attempts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Test Information')
                    ->schema([
                        Forms\Components\Select::make('module_test_id')
                            ->label('Module Test')
                            ->relationship('moduleTest', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name', fn ($query) => $query->where('role', 'student'))
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),
                    ])->columns(2),
                Forms\Components\Section::make('Test Results')
                    ->schema([
                        Forms\Components\ViewField::make('answers_display')
                            ->label('Answers')
                            ->view('filament.tutor.components.test-answers', ['answers' => fn ($record) => $record?->answers ?? []])
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Forms\Components\TextInput::make('total_questions')
                            ->label('Total Questions')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('percentage')
                            ->label('Percentage')
                            ->numeric()
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('is_passed')
                            ->label('Passed')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
                Forms\Components\Section::make('Timing')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['moduleTest', 'user'])
                ->whereHas('moduleTest', fn ($q) => $q->where('tutor_id', Auth::id())))
            ->columns([
                Tables\Columns\TextColumn::make('moduleTest.title')
                    ->label('Test')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($record) => $record->score . ' / ' . $record->total_questions)
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Percentage')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_passed')
                    ->label('Passed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_test_id')
                    ->label('Test')
                    ->relationship('moduleTest', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_passed')
                    ->label('Passed'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('completed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestAttempts::route('/'),
            'view' => Pages\ViewTestAttempt::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Test attempts are created by students taking tests
    }
}
