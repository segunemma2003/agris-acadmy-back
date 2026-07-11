<?php

namespace App\Filament\Resources\ApprenticeshipResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-heavy "timeline" of daily attendance/activity logs for an
 * apprenticeship — sorted most-recent-first, as the ticket's timeline view.
 * Logs mostly arrive via the web/USSD APIs; admin can add/correct one manually.
 */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Attendance Timeline';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('log_date')
                    ->required(),
                Forms\Components\Toggle::make('attended')
                    ->default(true),
                Forms\Components\Textarea::make('activity_description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

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
            ->defaultSort('log_date', 'desc');
    }
}
