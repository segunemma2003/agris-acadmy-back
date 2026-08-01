<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithCohortHeatMap;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class CohortHeatMap extends Page implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithCohortHeatMap;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.shared.cohort-heat-map';

    protected static ?string $navigationLabel = 'Cohort Heat Map';

    protected static ?string $title = 'Cohort overview (all learners)';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'cohort-heat-map';

    protected function heatMapFacilitatorScope(): ?int
    {
        // Platform admin sees every learner.
        return null;
    }

    protected function canForceRefreshCache(): bool
    {
        return true;
    }
}
