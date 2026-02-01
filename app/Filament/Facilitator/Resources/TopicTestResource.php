<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\TopicTestResource\Pages;
use App\Models\TopicTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TopicTestResource extends Resource
{
    protected static ?string $model = TopicTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Topic Tests';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Test Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', function ($query) {
                                $facilitatorLocation = Auth::user()->location;
                                return $query->whereHas('enrollments', function ($eq) use ($facilitatorLocation) {
                                    $eq->whereHas('user', function ($uq) use ($facilitatorLocation) {
                                        $uq->where('location', $facilitatorLocation);
                                    });
                                });
                            })
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('module_id')
                            ->label('Module')
                            ->relationship('module', 'title')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('topic_id')
                            ->label('Topic (Lesson)')
                            ->relationship('topic', 'title')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('title')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\RichEditor::make('description')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('passing_score')
                            ->label('Passing Score (%)')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('time_limit_minutes')
                            ->label('Time Limit (minutes)')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_active')
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $facilitatorLocation = Auth::user()->location;
        
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('course', function ($q) use ($facilitatorLocation) {
                $q->whereHas('enrollments', function ($eq) use ($facilitatorLocation) {
                    $eq->whereHas('user', function ($uq) use ($facilitatorLocation) {
                        $uq->where('location', $facilitatorLocation);
                    });
                });
            }))
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('module.title')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('topic.title')
                    ->label('Topic')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
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
                    ->relationship('course', 'title', function ($query) use ($facilitatorLocation) {
                        $query->whereHas('enrollments', function ($eq) use ($facilitatorLocation) {
                            $eq->whereHas('user', function ($uq) use ($facilitatorLocation) {
                                $uq->where('location', $facilitatorLocation);
                            });
                        });
                    })
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopicTests::route('/'),
            'view' => Pages\ViewTopicTest::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

