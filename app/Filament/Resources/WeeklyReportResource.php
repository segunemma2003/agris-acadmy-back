<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeeklyReportResource\Pages;
use App\Filament\Resources\WeeklyReportResource\RelationManagers;
use App\Models\WeeklyReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeeklyReportResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Weekly Reports';
    
    protected static ?string $navigationGroup = 'Communication';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Information')
                    ->schema([
                        Forms\Components\TextInput::make('tutor.name')
                            ->label('Tutor')
                            ->disabled(),
                        Forms\Components\TextInput::make('course.title')
                            ->label('Course')
                            ->disabled(),
                        Forms\Components\TextInput::make('report_week_start')
                            ->label('Week Start')
                            ->disabled(),
                        Forms\Components\TextInput::make('report_week_end')
                            ->label('Week End')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                                'reviewed' => 'Reviewed',
                            ])
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Report Content')
                    ->schema([
                        Forms\Components\RichEditor::make('weekly_plan')
                            ->label('Weekly Plan')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('achievements')
                            ->label('Achievements')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('activities_completed')
                            ->label('Activities Completed')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('challenges')
                            ->label('Challenges')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('next_week_plans')
                            ->label('Next Week Plans')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('total_students')
                            ->label('Total Students')
                            ->disabled(),
                        Forms\Components\TextInput::make('active_students')
                            ->label('Active Students')
                            ->disabled(),
                        Forms\Components\TextInput::make('completed_assignments')
                            ->label('Completed Assignments')
                            ->disabled(),
                    ])->columns(3),
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\Placeholder::make('images_info')
                            ->label('Uploaded Images')
                            ->content(function ($record) {
                                if (!$record || empty($record->images)) {
                                    return 'No images uploaded';
                                }
                                $html = '<div class="grid grid-cols-2 md:grid-cols-3 gap-4">';
                                foreach ($record->images as $image) {
                                    $url = Storage::url($image);
                                    $html .= '<div><img src="' . $url . '" alt="Report Image" class="w-full h-48 object-cover rounded-lg border"></div>';
                                }
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('video_links_info')
                            ->label('Video Links')
                            ->content(function ($record) {
                                if (!$record || empty($record->video_links)) {
                                    return 'No video links';
                                }
                                $html = '<ul class="list-disc list-inside">';
                                foreach ($record->video_links as $link) {
                                    $url = $link['url'] ?? $link;
                                    $html .= '<li><a href="' . $url . '" target="_blank" class="text-blue-600 hover:underline">' . $url . '</a></li>';
                                }
                                $html .= '</ul>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('advice_info')
                            ->label('Advice')
                            ->content(fn ($record) => $record && $record->advice ? $record->advice : 'No advice provided')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record && (!empty($record->images) || !empty($record->video_links) || !empty($record->advice))),
                Forms\Components\Section::make('Admin Feedback')
                    ->schema([
                        Forms\Components\RichEditor::make('admin_feedback')
                            ->label('Feedback')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tutor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_week_start')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_week_end')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_students')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_students')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_assignments')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'reviewed' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                    ]),
                Tables\Filters\SelectFilter::make('tutor_id')
                    ->label('Tutor')
                    ->relationship('tutor', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_reviewed')
                    ->label('Mark as Reviewed')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'reviewed',
                            'reviewed_at' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyReports::route('/'),
            'view' => Pages\ViewWeeklyReport::route('/{record}'),
            'edit' => Pages\EditWeeklyReport::route('/{record}/edit'),
        ];
    }
}
