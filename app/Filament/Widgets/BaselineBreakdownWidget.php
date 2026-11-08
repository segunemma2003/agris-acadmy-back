<?php

namespace App\Filament\Widgets;

use App\Support\BaselineStats;
use Filament\Widgets\Widget;

/** Admin dashboard: WEO learner baseline breakdowns (employment, income, inclusion, track, objective). */
class BaselineBreakdownWidget extends Widget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.baseline-breakdown';

    protected function getViewData(): array
    {
        $s = BaselineStats::get();

        return ['groups' => [
            'Employment status' => $s['employment'],
            'Monthly income tier (past 3 months)' => $s['income'],
            'Disability, difficulty or neurodivergence' => $s['disability_types'],
            'Value chain track' => $s['value_chain_track'],
            'Post-training objective' => $s['training_objective'],
        ]];
    }
}
