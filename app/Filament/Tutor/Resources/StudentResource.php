<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\StudentResource\Pages;
use App\Models\Course;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'My Students';

    protected static ?string $modelLabel = 'Student';

    public static function getEloquentQuery(): Builder
    {
        $courseIds = Course::query()
            ->accessibleByTutor(Auth::id())
            ->pluck('id');

        return parent::getEloquentQuery()
            ->where('role', 'student')
            ->whereHas('enrollments', fn ($q) => $q->whereIn('course_id', $courseIds));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('bio')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('Enrollments')
                    ->counts('enrollments')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'view' => Pages\ViewStudent::route('/{record}'),
        ];
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
