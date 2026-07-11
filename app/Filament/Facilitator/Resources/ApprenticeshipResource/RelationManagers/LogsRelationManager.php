<?php

namespace App\Filament\Facilitator\Resources\ApprenticeshipResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only attendance timeline for a facilitator's own assigned learners —
 * facilitators observe, they don't edit; corrections go through admin.
 */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Attendance Timeline';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('log_date')
            ->columns([
                Tables\Columns\TextColumn::make('log_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('attended')
                    ->boolean(),
                Tables\Columns\TextColumn::make('activity_description')
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\TextColumn::make('source')
                    ->badge(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('log_date', 'desc');
    }
}
