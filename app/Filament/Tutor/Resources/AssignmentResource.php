<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Information')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        Forms\Components\Select::make('module_id')
                            ->label('Module (Optional)')
                            ->relationship('module', 'title', fn (Builder $query, callable $get) =>
                                $query->where('course_id', $get('course_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('instructions')
                            ->label('Instructions')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('max_score')
                            ->label('Maximum Score')
                            ->numeric()
                            ->default(100)
                            ->required(),
                        Forms\Components\DateTimePicker::make('due_date')
                            ->label('Due Date')
                            ->native(false),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('tutor_id', auth()->id()))
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
                Tables\Columns\TextColumn::make('max_score')
                    ->label('Max Score')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\Action::make('submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Assignment $record) => AssignmentSubmissionResource::getUrl('index', ['assignment_id' => $record->id])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tutor_id'] = auth()->id();
        if (isset($data['course_id'])) {
            $course = \App\Models\Course::find($data['course_id']);
            if ($course && !$course->tutor_id) {
                $course->update(['tutor_id' => auth()->id()]);
            }
        }
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}

