<?php

namespace App\Filament\Concerns;

use App\Models\LearnerInterventionAlert;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** Learner alerts table shared by the admin and facilitator panels. */
trait LearnerAlertTable
{
    public static function alertTable(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('learner_name')
                    ->label('Learner')
                    ->description(fn (LearnerInterventionAlert $r) => $r->payload['email'] ?? null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => LearnerInterventionAlert::REASONS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'quiz_failed_twice' => 'danger',
                        'stalled_course' => 'warning',
                        'never_started' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('stuck_label')->label('Where')->wrap()->limit(60),
                Tables\Columns\TextColumn::make('payload.state')->label('State')->toggleable(),
                Tables\Columns\TextColumn::make('last_login_at')->label('Last login')->since()->placeholder('Never'),
                Tables\Columns\TextColumn::make('created_at')->label('Flagged')->since()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->state(fn (LearnerInterventionAlert $r) => $r->resolved_at ? ($r->auto_resolved ? 'Re-engaged' : 'Resolved') : 'Open')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Open' ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('resolution_note')->label('Note')->limit(50)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'resolved' => 'Resolved by staff', 'reengaged' => 'Re-engaged (auto)'])
                    ->default('open')
                    ->query(fn (Builder $q, array $data) => match ($data['value'] ?? null) {
                        'open' => $q->whereNull('resolved_at'),
                        'resolved' => $q->whereNotNull('resolved_at')->where('auto_resolved', false),
                        'reengaged' => $q->whereNotNull('resolved_at')->where('auto_resolved', true),
                        default => $q,
                    }),
                Tables\Filters\SelectFilter::make('reason')->options(LearnerInterventionAlert::REASONS),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LearnerInterventionAlert $r) => $r->resolved_at === null)
                    ->form([
                        Forms\Components\Textarea::make('resolution_note')
                            ->label('What was done?')
                            ->placeholder('e.g. Called the learner — they had no data; sent offline materials.')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(fn (LearnerInterventionAlert $r, array $data) => $r->forceFill([
                        'resolved_at' => now(), 'resolved_by' => Auth::id(), 'auto_resolved' => false,
                        'resolution_note' => $data['resolution_note'],
                    ])->save()),
                Tables\Actions\Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (LearnerInterventionAlert $r) => $r->resolved_at !== null)
                    ->requiresConfirmation()
                    ->action(fn (LearnerInterventionAlert $r) => $r->forceFill([
                        'resolved_at' => null, 'resolved_by' => null, 'auto_resolved' => false, 'resolution_note' => null,
                    ])->save()),
            ])
            ->emptyStateHeading('No learner alerts')
            ->emptyStateDescription('Learners who go inactive, fail a quiz twice, stall in a course or never start one appear here after the nightly check.');
    }
}
