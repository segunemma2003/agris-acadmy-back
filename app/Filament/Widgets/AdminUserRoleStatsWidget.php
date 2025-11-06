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
        // Get one user per role to use as a representative record
        // This ensures we have proper User models with IDs
        $roles = ['admin', 'tutor', 'student'];
        $userIds = collect($roles)->map(function ($role) {
            return User::where('role', $role)->value('id');
        })->filter()->toArray();

        return $table
            ->query(
                User::query()->whereIn('id', $userIds)
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
                    ->getStateUsing(function ($record) {
                        return User::where('role', $record->role)->count();
                    })
                    ->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->description('Breakdown of users by their roles');
    }
}

