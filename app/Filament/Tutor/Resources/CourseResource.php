<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Course Details')
                    ->schema([
                        Forms\Components\Repeater::make('what_you_will_learn')
                            ->label('What You Will Learn')
                            ->schema([
                                Forms\Components\TextInput::make('item')
                                    ->required(),
                            ])
                            ->defaultItems(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->directory('courses')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Select::make('level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->default('beginner'),
                        Forms\Components\TextInput::make('language')
                            ->default('English')
                            ->maxLength(255),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('tutor_id', auth()->id()))
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollment_count')
                    ->label('Enrollments')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Tutor\Resources\CourseResource\RelationManagers\ModulesRelationManager::class,
            \App\Filament\Tutor\Resources\CourseResource\RelationManagers\EnrollmentsRelationManager::class,
            \App\Filament\Tutor\Resources\CourseResource\RelationManagers\AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tutor_id'] = auth()->id();
        return $data;
    }
}



