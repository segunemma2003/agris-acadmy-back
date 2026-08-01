<?php

namespace App\Filament\Facilitator\Pages;

use App\Filament\Concerns\InteractsWithCohortHeatMap;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CohortHeatMap extends Page implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithCohortHeatMap;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.shared.cohort-heat-map';

    protected static ?string $navigationLabel = 'Cohort Heat Map';

    protected static ?string $title = 'Cohort overview';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'cohort-heat-map';

    protected function heatMapFacilitatorScope(): ?int
    {
        return Auth::id();
    }

    protected function canForceRefreshCache(): bool
    {
        // Facilitators may reload the snapshot; only admins rebuild globally.
        return false;
    }
}
