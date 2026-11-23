<?php

namespace App\Filament\Shared\RelationManagers;

use App\Models\CourseVrContent;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * "VR & Simulations" tab on a course page (admin and tutor panels): every 360° scene
 * and farm simulation in the course, with open in VR Studio, edit, hide / show and remove.
 */
class VrContentRelationManager extends RelationManager
{
    protected static string $relationship = 'vrContent';

    protected static ?string $title = 'VR & Simulations';

    protected static ?string $icon = 'heroicon-o-cube';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $n = $ownerRecord->vrContent()->count();

        return $n ? (string) $n : null;
    }

    /** The VR content resource of the panel we are in. */
    protected static function resource(): string
    {
        return Filament::getCurrentPanel()?->getId() === 'tutor'
            ? \App\Filament\Tutor\Resources\CourseVrContentResource::class
            : \App\Filament\Resources\CourseVrContentResource::class;
    }

    public function table(Table $table): Table
    {
        $resource = static::resource();

        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('kind')
                    ->label('Type')
                    ->badge()
                    ->state(function (CourseVrContent $record) {
                        if (! $record->isSimulation()) {
                            return '360° scene';
                        }
                        $engine = $record->scene_json['engine'] ?? '';

                        return str_contains($engine, 'poultry') ? 'Poultry simulation' : 'Fish pond simulation';
                    })
                    ->color(fn ($state) => $state === '360° scene' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('studio_status')->label('Status')->badge()
                    ->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_active')->label('Visible to learners')->boolean(),
                Tables\Columns\TextColumn::make('sim_attempts_count')->label('Attempts')->counts('simAttempts'),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->since()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('add')
                    ->label('Add VR content')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => $resource::getUrl('create', ['course_id' => $this->getOwnerRecord()->getKey()])),
                Tables\Actions\Action::make('library')
                    ->label('Use a ready-made simulation')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->url(fn () => rtrim((string) config('services.vr_studio.url'), '/').'/sim', shouldOpenInNewTab: true)
                    ->visible(fn () => filled(config('services.vr_studio.url'))),
            ])
            ->actions([
                $resource::openVrStudioTableAction(),
                Tables\Actions\Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (CourseVrContent $record) => $resource::getUrl('edit', ['record' => $record])),
                Tables\Actions\Action::make('toggle')
                    ->label(fn (CourseVrContent $record) => $record->is_active ? 'Hide from learners' : 'Show to learners')
                    ->icon(fn (CourseVrContent $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(function (CourseVrContent $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()->title($record->is_active ? 'Visible to learners again' : 'Hidden from learners — scores are kept')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove from the course?')
                    ->modalDescription('This deletes it from the course for good, including learners\' scores and attempts for it. To keep the scores, use "Hide from learners" instead.'),
            ])
            ->defaultSort('sort_order')
            ->emptyStateHeading('No VR content in this course yet')
            ->emptyStateDescription('Add a 360° scene or a farm simulation, or pick a ready-made simulation in VR Studio.');
    }
}
