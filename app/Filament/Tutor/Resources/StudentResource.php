<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\StudentResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'My Students';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->directory('avatars')
                            ->avatar(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'student')
                ->whereHas('enrollments.course', fn ($q) => $q->where('tutor_id', auth()->id())))
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('Enrolled Courses')
                    ->counts('enrollments', fn ($query) => $query->whereHas('course', fn ($q) => $q->where('tutor_id', auth()->id())))
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_progress')
                    ->label('View Progress')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (User $record) => StudentProgressResource::getUrl('index', ['student_id' => $record->id])),
                Tables\Actions\Action::make('send_message')
                    ->label('Send Message')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('enrollments.course', 'title', fn ($query) => $query->where('tutor_id', auth()->id()))
                            ->required(),
                        Forms\Components\TextInput::make('subject')
                            ->required(),
                        Forms\Components\RichEditor::make('message')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        \App\Models\Message::create([
                            'course_id' => $data['course_id'],
                            'sender_id' => auth()->id(),
                            'recipient_id' => $record->id,
                            'subject' => $data['subject'],
                            'message' => $data['message'],
                            'is_read' => false,
                        ]);
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'view' => Pages\ViewStudent::route('/{record}'),
        ];
    }
}

