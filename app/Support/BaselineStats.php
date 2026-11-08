<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * WEO learner baseline figures for the admin dashboard (MEL tool: Part 1
 * demographics + the 4 WEO fields). Cached for 10 minutes.
 */
class BaselineStats
{
    public const DISABILITY_TARGET = 10; // % donor quota

    public static function get(): array
    {
        return Cache::remember('baseline-stats', 600, fn () => self::compute());
    }

    public static function compute(): array
    {
        $learners = User::query()->where('role', 'student');
        $total = (clone $learners)->count();

        $count = fn (string $col, array $labels) => self::bars(
            (clone $learners)->whereNotNull($col)->selectRaw("{$col} as k, count(*) as n")->groupBy($col)->pluck('n', 'k')->all(),
            $labels,
        );

        // Disability types: JSON arrays, earlier answers folded into the current categories.
        $types = [];
        (clone $learners)->where('has_disability', 'yes')->whereNotNull('disability_types')
            ->select('id', 'disability_types')->chunkById(1000, function ($rows) use (&$types) {
                foreach ($rows as $u) {
                    foreach (array_unique(array_map(fn ($t) => ParticipantProfile::DISABILITY_TYPE_MAP[$t] ?? $t, (array) $u->disability_types)) as $t) {
                        $types[$t] = ($types[$t] ?? 0) + 1;
                    }
                }
            });

        $disabilityAnswered = (clone $learners)->whereNotNull('has_disability')->count();
        $disabilityYes = (clone $learners)->where('has_disability', 'yes')->count();
        $workAnswered = (clone $learners)->whereNotNull('main_work')->count();
        $incomeAnswered = (clone $learners)->whereNotNull('income_band')->where('income_band', '!=', 'prefer_not_to_say')->count();
        $belowMinWage = (clone $learners)->whereIn('income_band', ['none', 'under_30000', '30000_70000'])->count();
        $seeking = (clone $learners)->whereIn('main_work', ['unemployed_seeking', 'looking'])->count();
        $women = (clone $learners)->where('gender', 'female')->count();

        $employmentLabels = ParticipantProfile::MAIN_WORK + ParticipantProfile::MAIN_WORK_LEGACY;

        return [
            'total' => $total,
            'with_baseline' => $workAnswered,
            'women' => $women,
            'disability_answered' => $disabilityAnswered,
            'disability_yes' => $disabilityYes,
            'disability_rate' => self::pct($disabilityYes, $disabilityAnswered),
            'needs_accommodation' => (clone $learners)->where('needs_accommodation', 'yes')->count(),
            'seeking_rate' => self::pct($seeking, $workAnswered),
            'below_min_wage_rate' => self::pct($belowMinWage, $incomeAnswered),
            'employment' => $count('main_work', array_map(fn ($l) => self::short($l), $employmentLabels)),
            'income' => $count('income_band', ParticipantProfile::INCOME_CLASS),
            'disability_types' => self::bars($types, array_map(fn ($l) => self::short($l), ParticipantProfile::DISABILITY_TYPES + ParticipantProfile::DISABILITY_TYPES_LEGACY)),
            'value_chain_track' => $count('value_chain_track', ParticipantProfile::VALUE_CHAIN_TRACK),
            'training_objective' => $count('training_objective', ParticipantProfile::TRAINING_OBJECTIVE),
        ];
    }

    /** [label, count, % of answers] rows in the option order, empty options dropped. */
    private static function bars(array $counts, array $labels): array
    {
        $sum = array_sum($counts);
        $rows = [];
        foreach ($labels as $key => $label) {
            if (! empty($counts[$key])) {
                $rows[] = ['label' => $label, 'count' => $counts[$key], 'pct' => self::pct($counts[$key], $sum)];
            }
        }
        foreach ($counts as $key => $n) { // values with no label
            if (! isset($labels[$key]) && $n) {
                $rows[] = ['label' => (string) $key, 'count' => $n, 'pct' => self::pct($n, $sum)];
            }
        }
        return $rows;
    }

    private static function pct(int $n, int $of): float
    {
        return $of ? round($n / $of * 100, 1) : 0.0;
    }

    /** Label without its "(…)" explanation. */
    private static function short(string $label): string
    {
        return trim(preg_replace('/\s*\(.*\)\s*$/', '', $label));
    }
}
