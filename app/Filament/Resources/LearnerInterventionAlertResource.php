<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\LearnerAlertTable;
use App\Filament\Resources\LearnerInterventionAlertResource\Pages;
use App\Models\LearnerInterventionAlert;
use Filament\Resources\Resource;
use Filament\Tables\Table;

/** At-risk learners flagged by the nightly check (learners:send-intervention-alerts). */
class LearnerInterventionAlertResource extends Resource
{
    use LearnerAlertTable;

    protected static ?string $model = LearnerInterventionAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Learner Alerts';

    protected static ?string $modelLabel = 'learner alert';

    protected static ?string $navigationGroup = 'User Management';

    public static function getNavigationBadge(): ?string
    {
        $open = LearnerInterventionAlert::open()->count();
        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return static::alertTable($table);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListLearnerInterventionAlerts::route('/')];
    }
}
