<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\CourseResource\Pages;
use App\Filament\Tutor\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->accessibleByTutor(Auth::id())
            ->with(['category', 'tutor', 'tutors']);
    }

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
                            ->disk('public')
                            ->visibility('public')
                            ->directory('courses')
                            ->preserveFilenames()
                            ->imagePreviewHeight('250')
                            ->panelAspectRatio('16:9')
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
                Forms\Components\Section::make('Publishing')
                    ->description('Publish or unpublish this course. Pricing and platform featuring are managed by platform admins.')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->helperText('When published, enrolled learners can access the course.')
                            ->default(false),
                    ]),
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
                Tables\Columns\TextColumn::make('enrollment_count')
                    ->label('Enrollments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('togglePublish')
                    ->label(fn (Course $record) => $record->is_published ? 'Unpublish' : 'Publish')
                    ->icon(fn (Course $record) => $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Course $record) => $record->is_published ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Course $record) {
                        $record->is_published = ! $record->is_published;
                        $record->save();
                    }),
            ])
            ->bulkActions([]);
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
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
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
        return static::ownsRecord($record);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return static::ownsRecord($record);
    }

    protected static function ownsRecord($record): bool
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'tutor' || ! $record instanceof Course) {
            return false;
        }

        return $record->isAccessibleByTutor($user->id);
    }
}
