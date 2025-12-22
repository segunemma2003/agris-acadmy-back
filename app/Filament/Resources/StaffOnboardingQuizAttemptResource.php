<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffOnboardingQuizAttemptResource\Pages;
use App\Filament\Resources\StaffOnboardingQuizAttemptResource\RelationManagers;
use App\Models\StaffOnboardingQuizAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffOnboardingQuizAttemptResource extends Resource
{
    protected static ?string $model = StaffOnboardingQuizAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attempt Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name', fn ($query) => $query->where('role', 'tutor'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        Forms\Components\TextInput::make('score')
                            ->label('Score')
                            ->required()
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_questions')
                            ->label('Total Questions')
                            ->required()
                            ->numeric()
                            ->default(15)
                            ->disabled(),
                        Forms\Components\TextInput::make('percentage')
                            ->label('Percentage')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_passed')
                            ->label('Passed (70% required)')
                            ->required()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('started_at')
                            ->required()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Answers')
                    ->schema([
                        Forms\Components\Textarea::make('answers_display')
                            ->label('User Answers (JSON)')
                            ->formatStateUsing(fn ($record) => json_encode($record->answers, JSON_PRETTY_PRINT))
                            ->disabled()
                            ->columnSpanFull()
                            ->rows(10),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($record) => "{$record->score}/{$record->total_questions}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Percentage')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable()
                    ->color(fn ($record) => $record->is_passed ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('is_passed')
                    ->label('Passed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Staff Member')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'tutor'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_passed')
                    ->label('Passed Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('completed_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffOnboardingQuizAttempts::route('/'),
            'view' => Pages\ViewStaffOnboardingQuizAttempt::route('/{record}'),
        ];
    }
}
