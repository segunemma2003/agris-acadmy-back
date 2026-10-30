<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleTestResource\Pages;
use App\Filament\Resources\ModuleTestResource\RelationManagers;
use App\Filament\Support\CourseContentForms;
use App\Models\ModuleTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin: module tests are managed by drilling down from a course
 * (Course → Module → Topic → Test → Questions), so this resource isn't in
 * the sidebar; its edit page hosts the next level's tabs.
 */
class ModuleTestResource extends Resource
{
    protected static ?string $model = ModuleTest::class;

    protected static ?string $modelLabel = 'Module test';

    protected static ?string $pluralModelLabel = 'Module Tests';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Module test details')
                    ->schema([
                        Forms\Components\Placeholder::make('parent_module')->label('Course › Module')->content(fn ($record) => $record?->module ? $record->module->course?->title . ' › ' . $record->module->title : null)->columnSpanFull(),
                        ...CourseContentForms::test(80),
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
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModuleTests::route('/'),
            'edit' => Pages\EditModuleTest::route('/{record}/edit'),
        ];
    }
}
