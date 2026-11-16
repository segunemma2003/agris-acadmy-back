<?php

namespace App\Filament\Facilitator\Resources;

use App\Filament\Concerns\LearnerAlertTable;
use App\Filament\Facilitator\Resources\LearnerInterventionAlertResource\Pages;
use App\Models\LearnerInterventionAlert;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** The facilitator's own at-risk learners. */
class LearnerInterventionAlertResource extends Resource
{
    use LearnerAlertTable;

    protected static ?string $model = LearnerInterventionAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Learner Alerts';

    protected static ?string $modelLabel = 'learner alert';

    protected static ?string $navigationGroup = 'Student Management';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('facilitator_id', Auth::id());
    }

    public static function getNavigationBadge(): ?string
    {
        $open = static::getEloquentQuery()->whereNull('resolved_at')->count();
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
