<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopicResource\Pages;
use App\Filament\Resources\TopicResource\RelationManagers;
use App\Filament\Support\CourseContentForms;
use App\Models\Topic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin: topics are managed by drilling down from a course
 * (Course → Module → Topic → Test → Questions), so this resource isn't in
 * the sidebar; its edit page hosts the next level's tabs.
 */
class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $modelLabel = 'Topic';

    protected static ?string $pluralModelLabel = 'Topics';

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Topic details')
                    ->schema([
                        Forms\Components\Placeholder::make('parent_module')->label('Course › Module')->content(fn ($record) => $record?->module ? $record->module->course?->title . ' › ' . $record->module->title : null)->columnSpanFull(),
                        ...CourseContentForms::topic(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('module.course.title')->label('Course')->searchable(),
                Tables\Columns\TextColumn::make('module.title')->label('Module')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TestsRelationManager::class,
            RelationManagers\AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}
