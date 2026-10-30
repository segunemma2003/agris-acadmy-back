<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleResource\Pages;
use App\Filament\Resources\ModuleResource\RelationManagers;
use App\Filament\Support\CourseContentForms;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin: modules are managed by drilling down from a course
 * (Course → Module → Topic → Test → Questions), so this resource isn't in
 * the sidebar; its edit page hosts the next level's tabs.
 */
class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $modelLabel = 'Module';

    protected static ?string $pluralModelLabel = 'Modules';

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Module details')
                    ->schema([
                        Forms\Components\Placeholder::make('parent_course')->label('Course')->content(fn ($record) => $record?->course?->title),
                        ...CourseContentForms::module(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.title')->label('Course')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TopicsRelationManager::class,
            RelationManagers\TestsRelationManager::class,
            RelationManagers\AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModules::route('/'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }
}
