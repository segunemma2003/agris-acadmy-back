<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')
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
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('module_id', null)),
                        Forms\Components\Select::make('module_id')
                            ->label('Module (Optional)')
                            ->relationship('module', 'title', fn ($query, $get) => $query->where('course_id', $get('course_id')))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('instructions')
                            ->label('Instructions')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('max_score')
                            ->label('Max Score')
                            ->numeric()
                            ->default(100)
                            ->required(),
                        Forms\Components\DateTimePicker::make('due_date')
                            ->label('Due Date'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
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
                Tables\Columns\TextColumn::make('module.title')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
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
            'index' => Pages\ListAssignments::route('/'),
            'view' => Pages\ViewAssignment::route('/{record}'),
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