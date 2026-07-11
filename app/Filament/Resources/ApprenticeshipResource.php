<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprenticeshipResource\Pages;
use App\Filament\Resources\ApprenticeshipResource\RelationManagers\LogsRelationManager;
use App\Models\Apprenticeship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApprenticeshipResource extends Resource
{
    protected static ?string $model = Apprenticeship::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Apprenticeships (Career Pathways)';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'interested' => 'Interested',
                        'accepted' => 'Accepted (active placement)',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['slot.organisation', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Apprentice')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slot.title')
                    ->label('Slot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slot.organisation.name')
                    ->label('Organisation')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'interested' => 'Interested',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
            'edit' => Pages\EditApprenticeship::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
