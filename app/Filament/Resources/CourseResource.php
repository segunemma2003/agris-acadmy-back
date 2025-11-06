<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
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
                        Forms\Components\Select::make('tutor_id')
                            ->label('Tutor')
                            ->relationship('tutor', 'name', fn ($query) => $query->where('role', 'tutor'))
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
                        Forms\Components\Repeater::make('what_you_will_get')
                            ->label('What You Will Get')
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
                        Forms\Components\TagsInput::make('tags')
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Pricing & Settings')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\Toggle::make('is_free')
                            ->default(false),
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
                        Forms\Components\TextInput::make('materials_count')
                            ->label('Number of Materials')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->default(false),
                        Forms\Components\Toggle::make('is_featured')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollment_count')
                    ->label('Enrollments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('tutor_id')
                    ->label('Tutor')
                    ->relationship('tutor', 'name'),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulesRelationManager::class,
            RelationManagers\ResourcesRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
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
}



