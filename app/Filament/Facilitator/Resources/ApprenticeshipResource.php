<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Facilitator\Resources\ApprenticeshipResource\Pages;
use App\Filament\Facilitator\Resources\ApprenticeshipResource\RelationManagers\LogsRelationManager;
use App\Models\Apprenticeship;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only view of the facilitator's own assigned learners' apprenticeship
 * placements and attendance timelines — facilitators observe, they don't
 * manage slots or review applications (that's the organisation's job).
 */
class ApprenticeshipResource extends Resource
{
    protected static ?string $model = Apprenticeship::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Apprenticeships';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('user.name')->label('Apprentice'),
                TextEntry::make('slot.title')->label('Slot'),
                TextEntry::make('slot.organisation.name')->label('Organisation'),
                TextEntry::make('status')->badge(),
                TextEntry::make('reviewed_at')->dateTime(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['slot.organisation', 'user'])
                ->whereHas('user', fn ($q) => $q->where('facilitator_id', Auth::id()))
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Apprentice')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slot.title')
                    ->label('Slot'),
                Tables\Columns\TextColumn::make('slot.organisation.name')
                    ->label('Organisation'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('logs_count')
                    ->label('Logged Days')
                    ->counts('logs'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprenticeships::route('/'),
            'view' => Pages\ViewApprenticeship::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
