<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentCodeResource\Pages;
use App\Models\EnrollmentCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\EnrollmentCodeMail;

class EnrollmentCodeResource extends Resource
{
    protected static ?string $model = EnrollmentCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Course Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Code')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tutor_id')
                            ->relationship('tutor', 'name', fn ($query) => $query->where('role', 'tutor'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => EnrollmentCode::generateCode())
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->label('Email Address')
                            ->helperText('Email to send the enrollment code to'),
                        Forms\Components\DateTimePicker::make('expires_at'),
                        Forms\Components\Toggle::make('is_used')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned To')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_used')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'title'),
                Tables\Filters\TernaryFilter::make('is_used'),
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollmentCodes::route('/'),
            'create' => Pages\CreateEnrollmentCode::route('/create'),
            'edit' => Pages\EditEnrollmentCode::route('/{record}/edit'),
        ];
    }
}



