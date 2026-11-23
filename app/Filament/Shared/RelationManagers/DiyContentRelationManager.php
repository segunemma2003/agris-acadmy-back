<?php

namespace App\Filament\Shared\RelationManagers;

use App\Models\CourseDiyContent;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** "DIY Practicals" tab on a course page (admin and tutor panels). */
class DiyContentRelationManager extends RelationManager
{
    protected static string $relationship = 'diyContent';

    protected static ?string $title = 'DIY Practicals';

    protected static ?string $icon = 'heroicon-o-wrench-screwdriver';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $n = $ownerRecord->diyContent()->count();

        return $n ? (string) $n : null;
    }

    protected static function resource(): string
    {
        return Filament::getCurrentPanel()?->getId() === 'tutor'
            ? \App\Filament\Tutor\Resources\CourseDiyContentResource::class
            : \App\Filament\Resources\CourseDiyContentResource::class;
    }

    public function table(Table $table): Table
    {
        $resource = static::resource();

        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'submissions',
                'submissions as to_review_count' => fn ($q) => $q->where('status', 'submitted'),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('steps')->label('Steps')->state(fn ($record) => count($record->steps ?? [])),
                Tables\Columns\TextColumn::make('entries_required')->label('Records')
                    ->formatStateUsing(fn ($state, $record) => $state.' × '.strtolower($record->entry_label ?: 'entry')),
                Tables\Columns\TextColumn::make('submissions_count')->label('Learners'),
                Tables\Columns\TextColumn::make('to_review_count')->label('To review')->badge()->color(fn ($state) => $state ? 'warning' : 'gray'),
                Tables\Columns\IconColumn::make('is_active')->label('Visible to learners')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('add')
                    ->label('New DIY practical')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => $resource::getUrl('create', ['course_id' => $this->getOwnerRecord()->getKey()])),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review work')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn (CourseDiyContent $record) => $resource::submissionsUrl($record)),
                Tables\Actions\Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (CourseDiyContent $record) => $resource::getUrl('edit', ['record' => $record])),
                Tables\Actions\Action::make('toggle')
                    ->label(fn (CourseDiyContent $record) => $record->is_active ? 'Hide from learners' : 'Show to learners')
                    ->icon(fn (CourseDiyContent $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(function (CourseDiyContent $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()->title($record->is_active ? 'Visible to learners again' : 'Hidden from learners — their work is kept')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove this DIY practical?')
                    ->modalDescription('This deletes it and every learner\'s submitted work for it. To keep their work, use "Hide from learners" instead.'),
            ])
            ->defaultSort('sort_order')
            ->emptyStateHeading('No DIY practicals in this course yet')
            ->emptyStateDescription('Create one from a ready-made template (brooding chicks, pond water testing, seedlings) or from scratch.');
    }
}
