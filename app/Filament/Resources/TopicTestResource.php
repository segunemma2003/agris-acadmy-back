<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopicTestResource\Pages;
use App\Filament\Resources\TopicTestResource\RelationManagers;
use App\Filament\Support\CourseContentForms;
use App\Models\TopicTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin: topic tests are managed by drilling down from a course
 * (Course → Module → Topic → Test → Questions), so this resource isn't in
 * the sidebar; its edit page hosts the next level's tabs.
 */
class TopicTestResource extends Resource
{
    protected static ?string $model = TopicTest::class;

    protected static ?string $modelLabel = 'Topic test';

    protected static ?string $pluralModelLabel = 'Topic Tests';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Topic test details')
                    ->schema([
                        Forms\Components\Placeholder::make('parent_topic')->label('Module › Topic')->content(fn ($record) => $record?->topic ? $record->module?->title . ' › ' . $record->topic->title : null)->columnSpanFull(),
                        ...CourseContentForms::test(70),
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
            'index' => Pages\ListTopicTests::route('/'),
            'edit' => Pages\EditTopicTest::route('/{record}/edit'),
        ];
    }
}
