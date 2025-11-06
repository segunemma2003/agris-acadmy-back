<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AdminUserRoleStatsWidget extends BaseWidget
{
    protected static ?string $heading = 'Users by Role';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->select('role', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as id'))
                    ->groupBy('role')
            )
            ->columns([
                TextColumn::make('role')
                    ->label('User Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'tutor' => 'info',
                        'student' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                TextColumn::make('count')
                    ->label('Count')
                    ->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->description('Breakdown of users by their roles')
            ->recordKey(fn ($record): string => $record->role ?? (string) ($record->id ?? uniqid()));
    }
}

