<?php

namespace App\Filament\Widgets;

use App\Support\BaselineStats;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Admin dashboard: WEO learner baseline headline figures. */
class BaselineStatsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Learner baseline (WEO)';

    protected function getStats(): array
    {
        $s = BaselineStats::get();
        $target = BaselineStats::DISABILITY_TARGET;

        return [
            Stat::make('Learners with baseline', number_format($s['with_baseline']))
                ->description('of '.number_format($s['total']).' learners · '.number_format($s['women']).' women'),
            Stat::make('Living with disability', $s['disability_rate'].'%')
                ->description($s['disability_yes'].' learners · target '.$target.'%')
                ->color($s['disability_rate'] >= $target ? 'success' : 'warning'),
            Stat::make('Need adjustments to participate', number_format($s['needs_accommodation']))
                ->description('asked for physical, learning or workplace adjustments'),
            Stat::make('Unemployed & seeking work', $s['seeking_rate'].'%')
                ->description('of learners who answered'),
            Stat::make('Earn below minimum wage', $s['below_min_wage_rate'].'%')
                ->description('under ₦70,000 a month (past 3 months)')
                ->color('danger'),
        ];
    }
}
