<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AdminTableStatsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Courses by Enrollments';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Course::query()
                    ->withCount('enrollments')
                    ->orderBy('enrollments_count', 'desc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label('Enrollments')
                    ->counts('enrollments')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                    ->sortable(),
                TextColumn::make('is_published')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Published' : 'Draft')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
            ])
            ->defaultSort('enrollments_count', 'desc');
    }
}
