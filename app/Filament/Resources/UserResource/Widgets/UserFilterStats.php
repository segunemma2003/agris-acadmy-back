<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Support\ParticipantProfile;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Figures for exactly the users the table shows: they follow the tab,
 * the search and the applied filters (e.g. "registered in the last 30 days").
 */
class UserFilterStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListUsers::class;
    }

    protected function getStats(): array
    {
        $base = fn (): Builder => $this->getPageTableQuery()->reorder();
        $pct = fn (int $n, int $of) => $of ? round(100 * $n / $of).'%' : '—';

        $total = $base()->count();
        $learners = $base()->where('role', 'student')->count();
        $enrolled = $base()->whereHas('enrollments')->count();
        $completed = $base()->whereHas('enrollments', fn ($e) => $e->where('status', 'completed'))->count();
        $profile = ParticipantProfile::scopeCvReady($base())->count();
        $recent = $base()->where('last_login_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('Users', number_format($total))
                ->description($this->filterSummary())
                ->icon('heroicon-o-users'),
            Stat::make('Learners', number_format($learners))
                ->description($pct($learners, $total).' of these users')
                ->icon('heroicon-o-academic-cap'),
            Stat::make('Enrolled in a course', number_format($enrolled))
                ->description($pct($enrolled, $total).' · '.number_format($total - $enrolled).' not enrolled')
                ->color('success')
                ->icon('heroicon-o-book-open'),
            Stat::make('Completed a course', number_format($completed))
                ->description($pct($completed, $enrolled).' of those enrolled')
                ->color('success')
                ->icon('heroicon-o-check-badge'),
            Stat::make('Profile form complete', number_format($profile))
                ->description($pct($profile, $total).' · '.number_format($total - $profile).' incomplete')
                ->color($total && $profile / $total < 0.5 ? 'warning' : 'success')
                ->icon('heroicon-o-identification'),
            Stat::make('Signed in, last 30 days', number_format($recent))
                ->description($pct($recent, $total).' of these users')
                ->icon('heroicon-o-arrow-right-on-rectangle'),
        ];
    }

    private function filterSummary(): string
    {
        $active = collect($this->tableFilters ?? [])
            ->filter(fn ($f, $name) => $name === 'registered'
                ? array_filter(\App\Filament\Resources\UserResource::periodRange((array) $f)) !== []
                : collect($f)->filter(fn ($v) => filled($v))->isNotEmpty())
            ->count();
        $search = filled($this->tableSearch ?? null);

        return match (true) {
            $active > 0 || $search => 'Matching your '.($active ? $active.' filter'.($active > 1 ? 's' : '') : '').($active && $search ? ' and ' : '').($search ? 'search' : ''),
            default => 'No filters applied',
        };
    }
}
