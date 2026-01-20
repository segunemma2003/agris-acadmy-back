<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Message Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship(
                                'course', 
                                'title', 
                                fn ($query) => $query->where(function ($q) {
                                    $tutorId = Auth::id();
                                    $q->where('tutor_id', $tutorId)
                                      ->orWhereHas('tutors', fn ($query) => $query->where('tutor_id', $tutorId))
                                      ->orWhereHas('tutor', fn ($query) => $query->where('role', 'admin'));
                                })
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('send_to_all')
                            ->label('Send to All Enrolled Students')
                            ->default(false)
                            ->reactive()
                            ->helperText('If enabled, message will be sent to all students enrolled in the selected course'),
                        Forms\Components\Select::make('recipient_id')
                            ->label('To (Individual)')
                            ->relationship('recipient', 'name', fn ($query) => $query->where('role', 'student'))
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => !$get('send_to_all'))
                            ->required(fn ($get) => !$get('send_to_all')),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('message')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('course', fn ($q) => $q->accessibleByTutor(Auth::id())))
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('From')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient.name')
                    ->label('To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn ($record, $state) => $record->parent_id ? '↳ ' . $state : $state),
                Tables\Columns\TextColumn::make('parent_id')
                    ->label('Thread')
                    ->formatStateUsing(fn ($state) => $state ? 'Reply' : 'Original')
                    ->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title', fn ($query) => $query->where('tutor_id', Auth::id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Message Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('course.title')
                            ->label('Course'),
                        Infolists\Components\TextEntry::make('sender.name')
                            ->label('From'),
                        Infolists\Components\TextEntry::make('recipient.name')
                            ->label('To'),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Subject'),
                        Infolists\Components\TextEntry::make('message')
                            ->label('Message')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\IconEntry::make('is_read')
                            ->boolean()
                            ->label('Read Status'),
                    ])->columns(2),
                Infolists\Components\Section::make('Replies')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('replies')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('sender.name')
                                    ->label('From'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('message')
                                    ->label('Reply')
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->replies->count() > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'view' => Pages\ViewMessage::route('/{record}'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}

