<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Messages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Message Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('sender_id')
                            ->label('From (Student)')
                            ->relationship('sender', 'name', fn (Builder $query) => $query->where('role', 'student'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('message')
                            ->label('Message')
                            ->required()
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Reply')
                    ->schema([
                        Forms\Components\RichEditor::make('reply')
                            ->label('Your Reply')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record && $record->exists),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('recipient_id', auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('From')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->label('Read')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('read_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title', fn (Builder $query) => $query->where('tutor_id', auth()->id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_read')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(function (Message $record) {
                        $record->update([
                            'is_read' => true,
                            'read_at' => now(),
                        ]);
                    })
                    ->visible(fn ($record) => !$record->is_read),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'view' => Pages\ViewMessage::route('/{record}'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}

