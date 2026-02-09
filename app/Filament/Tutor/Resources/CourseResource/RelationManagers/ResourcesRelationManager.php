<?php

namespace App\Filament\Tutor\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Resource Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('resource_type')
                            ->label('Resource Type')
                            ->options([
                                'download' => 'File Download',
                                'link' => 'External Link',
                                'video' => 'Video',
                            ])
                            ->default('download')
                            ->required()
                            ->reactive()
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_free')
                            ->label('Free Resource')
                            ->default(true)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpan(1),
                        Forms\Components\Hidden::make('file_name'),
                        Forms\Components\Hidden::make('file_type'),
                        Forms\Components\Hidden::make('file_size'),
                    ])->columns(2),
                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Upload File')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('course-resources')
                            ->maxSize(10240) // 10MB
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'text/plain',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'video/mp4',
                                'video/quicktime',
                                'application/zip',
                                'application/x-rar-compressed',
                            ])
                            ->helperText('Max file size: 10MB. Supported: PDF, Word, Excel, PowerPoint, Images, Videos, ZIP, RAR')
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if ($state && $get('resource_type') === 'download') {
                                    // Extract file name from path
                                    $fileName = basename($state);
                                    $set('file_name', $fileName);
                                    
                                    // Try to get file info if file exists
                                    $fullPath = storage_path('app/public/' . $state);
                                    if (file_exists($fullPath)) {
                                        $set('file_size', filesize($fullPath));
                                        $set('file_type', mime_content_type($fullPath));
                                    }
                                }
                            })
                            ->visible(fn ($get) => $get('resource_type') === 'download')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('resource_type') === 'download'),
                Forms\Components\Section::make('External Link')
                    ->schema([
                        Forms\Components\TextInput::make('external_url')
                            ->label('URL')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Enter the full URL (e.g., https://example.com/resource)')
                            ->visible(fn ($get) => $get('resource_type') === 'link')
                            ->required(fn ($get) => $get('resource_type') === 'link')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('resource_type') === 'link'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resource_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'download' => 'success',
                        'link' => 'info',
                        'video' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'download' => 'File',
                        'link' => 'Link',
                        'video' => 'Video',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->limit(30)
                    ->visible(fn ($record) => $record->resource_type === 'download'),
                Tables\Columns\TextColumn::make('external_url')
                    ->label('URL')
                    ->limit(30)
                    ->url(fn ($record) => $record->external_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->resource_type === 'link'),
                Tables\Columns\IconColumn::make('is_free')
                    ->label('Free')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('resource_type')
                    ->options([
                        'download' => 'File Download',
                        'link' => 'External Link',
                        'video' => 'Video',
                    ]),
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Free Resources'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
