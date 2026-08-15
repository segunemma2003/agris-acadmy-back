<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\InteractsWithVrStudio;
use App\Filament\Resources\CourseVrContentResource\Pages;
use App\Models\CourseVrContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseVrContentResource extends Resource
{
    use InteractsWithVrStudio;

    protected static ?string $model = CourseVrContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'VR Content';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('VR Content Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('module_id')
                            ->label('Module (optional)')
                            ->relationship('module', 'title')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tutor_id')
                            ->label('Author tutor')
                            ->relationship(
                                'tutor',
                                'name',
                                fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('role', 'tutor')
                            )
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('instructions')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('cta_label')
                            ->default('Launch VR')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('vr_url')
                            ->label('VR URL (auto-set by Studio on publish)')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('studio_status')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->disk('public')
                            ->directory('vr-content')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->wrap(),
                Tables\Columns\TextColumn::make('studio_status')->badge(),
                Tables\Columns\TextColumn::make('vr_url')->limit(30)->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->actions([
                static::openVrStudioTableAction(),
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
            'index' => Pages\ListCourseVrContents::route('/'),
            'create' => Pages\CreateCourseVrContent::route('/create'),
            'edit' => Pages\EditCourseVrContent::route('/{record}/edit'),
        ];
    }
}
