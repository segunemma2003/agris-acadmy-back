<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\CourseVrContentResource\Pages;
use App\Models\CourseVrContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CourseVrContentResource extends Resource
{
    protected static ?string $model = CourseVrContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'VR Content';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('VR Content Details')
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
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('vr_url')
                            ->label('VR URL')
                            ->url()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('vr-content')
                            ->preserveFilenames()
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
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->circular(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseVrContents::route('/'),
            'view' => Pages\ViewCourseVrContent::route('/{record}'),
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