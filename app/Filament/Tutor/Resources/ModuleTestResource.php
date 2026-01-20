<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\ModuleTestResource\Pages;
use App\Filament\Tutor\Resources\ModuleTestResource\RelationManagers;
use App\Models\ModuleTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ModuleTestResource extends Resource
{
    protected static ?string $model = ModuleTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Tests';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Test Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship(
                                'course', 
                                'title', 
                                fn ($query) => $query->where(function ($q) {
                                    $tutorId = Auth::id();
                                    $q->where('tutor_id', $tutorId)
                                      ->orWhereHas('tutors', fn ($query) => $query->where('tutor_id', $tutorId))
                                      ->orWhereHas('tutor', fn ($query) => $query->where('role', 'admin'));
                                })
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('module_id', null)),
                        Forms\Components\Select::make('module_id')
                            ->label('Module')
                            ->relationship('module', 'title', fn ($query, $get) => $query->where('course_id', $get('course_id')))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('passing_score')
                            ->label('Passing Score (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(70)
                            ->required(),
                        Forms\Components\TextInput::make('time_limit_minutes')
                            ->label('Time Limit (minutes)')
                            ->numeric()
                            ->default(60)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Tutors can only view tests they created
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('tutor_id', Auth::id()))
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('module.title')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Passing Score')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_limit_minutes')
                    ->label('Time Limit')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship(
                        'course', 
                        'title', 
                        fn ($query) => $query->where(function ($q) {
                            $tutorId = Auth::id();
                            $q->where('tutor_id', $tutorId)
                              ->orWhereHas('tutors', fn ($query) => $query->where('tutor_id', $tutorId))
                              ->orWhereHas('tutor', fn ($query) => $query->where('role', 'admin'));
                        })
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModuleTests::route('/'),
            'create' => Pages\CreateModuleTest::route('/create'),
            'edit' => Pages\EditModuleTest::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return true; // Tutors can create tests
    }

    public static function canEdit($record): bool
    {
        // Tutors can only edit tests they created
        if (!$record->tutor_id) {
            return false; // Old records without tutor_id cannot be edited
        }
        return $record->tutor_id === Auth::id();
    }

    public static function canDelete($record): bool
    {
        // Tutors can only delete tests they created
        if (!$record->tutor_id) {
            return false; // Old records without tutor_id cannot be deleted
        }
        return $record->tutor_id === Auth::id();
    }
}

