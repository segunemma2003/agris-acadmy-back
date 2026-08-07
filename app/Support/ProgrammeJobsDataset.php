<?php

namespace App\Support;

/**
 * Jobs Created / Enterprise Hub figures and lists from TAGDEV workbooks only:
 * - AGRISITI-TAGDEV 2.0-JOBS ENABLED.xlsx
 * - AGRISITI -TAGDEV 2.0- ENTERPRISES CREATED.xlsx
 * Source pack: public/data/drive-download-20260807T151252Z-1-001
 */
class ProgrammeJobsDataset
{
    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $path = public_path('data/programme/programmeJobs.json');
        if (! is_file($path)) {
            return self::emptyPayload();
        }

        $raw = json_decode((string) file_get_contents($path), true);
        if (! is_array($raw)) {
            return self::emptyPayload();
        }

        $enterprises = collect($raw['enterprises'] ?? [])->values()->all();
        $jobs = collect($raw['jobs'] ?? [])->values()->all();
        $jobsEnabled = (int) ($raw['jobs_enabled'] ?? count($jobs));
        $enterprisesCreated = (int) ($raw['enterprises_created'] ?? count($enterprises));

        $jobsByState = collect($raw['jobs_by_state'] ?? [])->values()->all();
        $enterprisesByState = collect($raw['enterprises_by_state'] ?? [])->values()->all();
        $jobsByGender = collect($raw['jobs_by_gender'] ?? [])->values()->all();

        $femaleRow = collect($jobsByGender)->firstWhere('label', 'Female');
        $maleRow = collect($jobsByGender)->firstWhere('label', 'Male');
        $female = (int) ($femaleRow['value'] ?? 0);
        $male = (int) ($maleRow['value'] ?? 0);
        if ($female === 0 && $male === 0) {
            $female = collect($jobs)->where(fn ($j) => strcasecmp((string) ($j['gender'] ?? ''), 'Female') === 0)->count();
            $male = collect($jobs)->where(fn ($j) => strcasecmp((string) ($j['gender'] ?? ''), 'Male') === 0)->count();
        }

        $stats = [
            ['key' => 'jobs_enabled', 'label' => 'Jobs enabled', 'value' => $jobsEnabled, 'unit' => 'count'],
            ['key' => 'enterprises_created', 'label' => 'Enterprises created', 'value' => $enterprisesCreated, 'unit' => 'count'],
            ['key' => 'jobs_female', 'label' => 'Female (jobs)', 'value' => $female, 'unit' => 'count'],
            ['key' => 'jobs_male', 'label' => 'Male (jobs)', 'value' => $male, 'unit' => 'count'],
            ['key' => 'states_with_jobs', 'label' => 'States covered', 'value' => count($jobsByState) ?: 7, 'unit' => 'count'],
            ['key' => 'placed_interns', 'label' => 'Jobs enabled', 'value' => $jobsEnabled, 'unit' => 'count'],
            ['key' => 'host_companies', 'label' => 'Enterprises created', 'value' => $enterprisesCreated, 'unit' => 'count'],
        ];

        return [
            'programme_impact' => true,
            'stats' => $stats,
            'highlight_stats' => array_slice($stats, 0, 5),
            'breakdowns' => [
                ['title' => 'Jobs enabled by state', 'items' => $jobsByState],
                ['title' => 'Jobs by gender', 'items' => $jobsByGender],
                ['title' => 'Enterprises by state', 'items' => $enterprisesByState],
            ],
            'location_filters' => collect($jobsByState)
                ->map(fn ($row) => [
                    'key' => strtolower(trim((string) ($row['label'] ?? ''))),
                    'label' => (string) ($row['label'] ?? ''),
                    'value' => (int) ($row['value'] ?? 0),
                ])
                ->filter(fn ($row) => $row['key'] !== '')
                ->values()
                ->all(),
            'gender_filters' => collect($jobsByGender)
                ->map(fn ($row) => [
                    'key' => strtolower(trim((string) ($row['label'] ?? ''))),
                    'label' => (string) ($row['label'] ?? ''),
                    'value' => (int) ($row['value'] ?? 0),
                ])
                ->values()
                ->all(),
            'enterprises' => $enterprises,
            'jobs' => $jobs,
            'trend' => [
                'title' => 'Jobs enabled by state',
                'unit' => 'count',
                'points' => collect($jobsByState)
                    ->map(fn ($row) => [
                        'label' => (string) ($row['label'] ?? ''),
                        'value' => (int) ($row['value'] ?? 0),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyPayload(): array
    {
        return [
            'programme_impact' => true,
            'stats' => [
                ['key' => 'jobs_enabled', 'label' => 'Jobs enabled', 'value' => 0, 'unit' => 'count'],
                ['key' => 'enterprises_created', 'label' => 'Enterprises created', 'value' => 0, 'unit' => 'count'],
            ],
            'highlight_stats' => [],
            'breakdowns' => [],
            'location_filters' => [],
            'gender_filters' => [],
            'enterprises' => [],
            'jobs' => [],
            'trend' => ['title' => 'Jobs enabled by state', 'unit' => 'count', 'points' => []],
        ];
    }
}
