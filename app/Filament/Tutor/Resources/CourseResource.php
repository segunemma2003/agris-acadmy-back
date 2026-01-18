<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\CourseResource\Pages;
use App\Filament\Tutor\Resources\CourseResource\RelationManagers;
use App\Models\Category;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
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
                        Forms\Components\Select::make('tutors')
                            ->label('Additional Tutors')
                            ->relationship('tutors', 'name', fn ($query) => $query->where('role', 'tutor')->where('id', '!=', Auth::id()))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Select additional tutors for this course (excluding yourself)'),
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
                            ->disk(config('filesystems.default'))
                            ->directory('courses')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('course_information')
                            ->label('Course Information')
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Label')
                                    ->required(),
                                Forms\Components\Textarea::make('value')
                                    ->label('Value')
                                    ->required(),
                            ])
                            ->defaultItems(2)
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
        // Show courses where tutor is primary tutor, additional tutor, or course was created by admin
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where(function ($q) {
                $tutorId = Auth::id();
                // Primary tutor
                $q->where('tutor_id', $tutorId)
                  // Additional tutor
                  ->orWhereHas('tutors', fn ($query) => $query->where('tutor_id', $tutorId))
                  // Course created by admin
                  ->orWhereHas('tutor', fn ($query) => $query->where('role', 'admin'));
            }))
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
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tutors can only view courses, not edit or delete
            ])
            ->bulkActions([
                // Tutors cannot perform bulk actions on courses
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulesRelationManager::class,
            RelationManagers\ResourcesRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'view' => Pages\ViewCourse::route('/{record}'),
            // Tutors cannot create or edit courses - removed 'create' and 'edit' pages
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'tutor';
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

    public static function canView($record): bool
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'tutor') {
            return false;
        }

        $tutorId = $user->id;
        
        // Check if tutor has access to this course
        return $record->tutor_id === $tutorId
            || $record->tutors()->where('tutor_id', $tutorId)->exists()
            || ($record->tutor && $record->tutor->role === 'admin');
    }
}

