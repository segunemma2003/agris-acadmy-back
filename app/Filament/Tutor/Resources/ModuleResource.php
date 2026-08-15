<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\ModuleResource\Pages;
use App\Filament\Tutor\Resources\ModuleResource\RelationManagers;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('course', fn ($q) => $q->accessibleByTutor(Auth::id()))
            ->with('course');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Module Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship(
                                'course',
                                'title',
                                fn ($query) => $query->accessibleByTutor(Auth::id())
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('topics_count')
                    ->label('Topics')
                    ->counts('topics')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TestRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'view' => Pages\ViewModule::route('/{record}'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user && $user->role === 'tutor';
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user && $user->role === 'tutor';
    }

    public static function canEdit($record): bool
    {
        return static::ownsModule($record);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return static::ownsModule($record);
    }

    protected static function ownsModule($record): bool
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'tutor' || ! $record instanceof Module) {
            return false;
        }

        $course = $record->course;

        return $course && $course->isAccessibleByTutor($user->id);
    }
}
