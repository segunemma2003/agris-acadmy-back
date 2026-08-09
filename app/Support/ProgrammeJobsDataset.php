<?php

namespace App\Support;

/**
 * Jobs Enabled / Enterprise Hub figures and lists from TAGDEV workbooks only:
 * - AGRISITI-TAGDEV 2.0-JOBS ENABLED.xlsx
 * - AGRISITI -TAGDEV 2.0- ENTERPRISES CREATED.xlsx
 * Source pack: public/data/drive-download-20260807T151252Z-1-001
 */
class ProgrammeJobsDataset
{
    public static function jobsEnabledCount(): int
    {
        return (int) (self::raw()['jobs_enabled'] ?? count(self::raw()['jobs'] ?? []));
    }

    public static function enterprisesCreatedCount(): int
    {
        return (int) (self::raw()['enterprises_created'] ?? count(self::raw()['enterprises'] ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    private static function raw(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $path = public_path('data/programme/programmeJobs.json');
        if (! is_file($path)) {
            return $cached = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return $cached = is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{url:string,filename:string}|null
     */
    private static function workbookDownload(string $relativePath, string $filename): ?array
    {
        $absolute = public_path($relativePath);
        if (! is_file($absolute)) {
            return null;
        }

        return [
            'url' => url('/'.ltrim($relativePath, '/')),
            'filename' => $filename,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $raw = self::raw();
        if ($raw === []) {
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
        ];

        $downloads = array_filter([
            'jobs_enabled' => self::workbookDownload(
                'data/programme/AGRISITI-TAGDEV-2.0-JOBS-ENABLED.xlsx',
                'AGRISITI-TAGDEV-2.0-JOBS-ENABLED.xlsx',
            ),
            'enterprises_created' => self::workbookDownload(
                'data/programme/AGRISITI-TAGDEV-2.0-ENTERPRISES-CREATED.xlsx',
                'AGRISITI-TAGDEV-2.0-ENTERPRISES-CREATED.xlsx',
            ),
        ]);

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
            'downloads' => $downloads,
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
            'downloads' => [],
            'trend' => ['title' => 'Jobs enabled by state', 'unit' => 'count', 'points' => []],
        ];
    }
}
