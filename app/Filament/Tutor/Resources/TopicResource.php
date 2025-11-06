<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\TopicResource\Pages;
use App\Models\Topic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Topic Information')
                    ->schema([
                        Forms\Components\Select::make('module_id')
                            ->label('Module')
                            ->relationship('module', 'title', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('tutor_id', auth()->id())))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('sort_order', Topic::where('module_id', $state)->max('sort_order') + 1 ?? 1)),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\Select::make('content_type')
                            ->options([
                                'video' => 'Video',
                                'text' => 'Text',
                                'mixed' => 'Mixed',
                            ])
                            ->default('video')
                            ->required(),
                        Forms\Components\TextInput::make('video_url')
                            ->label('Video URL')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('transcript')
                            ->label('Video Transcript')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('write_up')
                            ->label('Content/Write-up')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_free')
                            ->label('Free Preview')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('module.course', fn ($q) => $q->where('tutor_id', auth()->id())))
            ->columns([
                Tables\Columns\TextColumn::make('module.course.title')
                    ->label('Course')
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
                Tables\Columns\TextColumn::make('content_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video' => 'success',
                        'text' => 'info',
                        'mixed' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' min' : '-')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_free')
                    ->boolean()
                    ->label('Free'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('tutor_id', auth()->id())))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('content_type'),
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Free Preview'),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}

