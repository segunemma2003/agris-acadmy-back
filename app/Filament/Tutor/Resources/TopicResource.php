<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\TopicResource\Pages;
use App\Models\Topic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Topic Details')
                    ->schema([
                        Forms\Components\Select::make('module_id')
                            ->label('Module')
                            ->relationship('module', 'title', fn ($query) => $query->whereHas('course', fn ($q) => $q->accessibleByTutor(Auth::id())))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('content_type')
                            ->options([
                                'video' => 'Video',
                                'text' => 'Text',
                                'audio' => 'Audio',
                                'interactive' => 'Interactive',
                            ])
                            ->default('video')
                            ->required(),
                        Forms\Components\TextInput::make('video_url')
                            ->label('Video URL')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('transcript')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('write_up')
                            ->label('Write Up')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_free')
                            ->label('Free Preview')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
                Forms\Components\Section::make('Classwork / Assignments')
                    ->description('Add assignments or classwork for this topic')
                    ->schema([
                        Forms\Components\Repeater::make('classwork')
                            ->label('Classwork Items')
                            ->relationship('assignments')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Forms\Components\RichEditor::make('description')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\RichEditor::make('instructions')
                                    ->label('Instructions')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('max_score')
                                    ->label('Maximum Score')
                                    ->numeric()
                                    ->default(100)
                                    ->required(),
                                Forms\Components\DateTimePicker::make('due_date')
                                    ->label('Due Date')
                                    ->timezone('UTC'),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel('Add Classwork')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('module.course', fn ($q) => $q->accessibleByTutor(Auth::id())))
            ->columns([
                Tables\Columns\TextColumn::make('module.course.title')
                    ->label('Course')
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
                Tables\Columns\TextColumn::make('content_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_free')
                    ->label('Free')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title', fn ($query) => $query->whereHas('course', fn ($q) => $q->where('tutor_id', Auth::id())))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('content_type'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Assignments can be managed through the form or separately
        ];
    }

    public static function canCreate(): bool
    {
        return true; // Tutors can create topics
    }

    public static function canEdit($record): bool
    {
        // All tutors can edit topics for modules they have access to
        return $record->module && $record->module->course && $record->module->course->accessibleByTutor(Auth::id());
    }

    public static function canDelete($record): bool
    {
        // All tutors can delete topics for modules they have access to
        return $record->module && $record->module->course && $record->module->course->accessibleByTutor(Auth::id());
    }
}

