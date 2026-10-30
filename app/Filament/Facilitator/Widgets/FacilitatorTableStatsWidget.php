<?php

namespace App\Filament\Facilitator\Widgets;

use App\Models\Course;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class FacilitatorTableStatsWidget extends BaseWidget
{
    // Not shown yet: under its old filename (Supervisor*Widget.php) Filament's
    // discovery never registered this widget. Set to true to add it to the
    // facilitator dashboard.
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'My Courses Performance';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Course::query()
                    ->where('tutor_id', Auth::id())
                    ->withCount('enrollments')
                    ->orderBy('enrollments_count', 'desc')
            )
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
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
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('enrollments_count', 'desc');
    }
}
