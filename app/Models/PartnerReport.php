<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerReport extends Model
{
    protected $fillable = [
        'partner_id',
        'created_by',
        'title',
        'period_type',
        'period_start',
        'period_end',
        'summary',
        'participants_registered_count',
        'participants_selected_count',
        'participants_enrolled_count',
        'jobs_enabled',
        'jobs_created',
        'demo_hubs',
        'enterprises_created',
        'participants_registered',
        'participants_selected',
        'participants_enrolled',
        'google_doc_links',
        'image_links',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'participants_registered' => 'array',
            'participants_selected' => 'array',
            'participants_enrolled' => 'array',
            'google_doc_links' => 'array',
            'image_links' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Payload shape consumed by the partner dashboard.
     */
    public function toPartnerPayload(): array
    {
        $registered = $this->normalizeParticipants($this->participants_registered);
        $selected = $this->normalizeParticipants($this->participants_selected);
        $enrolled = $this->normalizeParticipants($this->participants_enrolled);

        $registeredCount = max((int) $this->participants_registered_count, count($registered));
        $selectedCount = max((int) $this->participants_selected_count, count($selected));
        $enrolledCount = max((int) $this->participants_enrolled_count, count($enrolled));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'period_type' => $this->period_type,
            'period_label' => ucfirst($this->period_type),
            'period_start' => optional($this->period_start)->toDateString(),
            'period_end' => optional($this->period_end)->toDateString(),
            'summary' => $this->summary,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'stats' => [
                [
                    'key' => 'participants_registered',
                    'label' => 'Participants registered / reached',
                    'value' => $registeredCount,
                    'unit' => 'count',
                ],
                [
                    'key' => 'participants_selected',
                    'label' => 'Participants selected',
                    'value' => $selectedCount,
                    'unit' => 'count',
                ],
                [
                    'key' => 'participants_enrolled',
                    'label' => 'Enrolled',
                    'value' => $enrolledCount,
                    'unit' => 'count',
                ],
                [
                    'key' => 'jobs_enabled',
                    'label' => 'Jobs enabled',
                    'value' => (int) $this->jobs_enabled,
                    'unit' => 'count',
                ],
                [
                    'key' => 'jobs_created',
                    'label' => 'Jobs created',
                    'value' => (int) $this->jobs_created,
                    'unit' => 'count',
                ],
                [
                    'key' => 'demo_hubs',
                    'label' => 'Demo hubs / Dignity in labour',
                    'value' => (int) $this->demo_hubs,
                    'unit' => 'count',
                ],
                [
                    'key' => 'enterprises_created',
                    'label' => 'Enterprises created',
                    'value' => (int) $this->enterprises_created,
                    'unit' => 'count',
                ],
            ],
            'breakdowns' => [
                [
                    'title' => 'Participant funnel',
                    'items' => [
                        ['label' => 'Registered / reached', 'value' => $registeredCount],
                        ['label' => 'Selected', 'value' => $selectedCount],
                        ['label' => 'Enrolled', 'value' => $enrolledCount],
                    ],
                ],
                [
                    'title' => 'Jobs & enterprise impact',
                    'items' => [
                        ['label' => 'Jobs enabled', 'value' => (int) $this->jobs_enabled],
                        ['label' => 'Jobs created', 'value' => (int) $this->jobs_created],
                        ['label' => 'Demo hubs', 'value' => (int) $this->demo_hubs],
                        ['label' => 'Enterprises', 'value' => (int) $this->enterprises_created],
                    ],
                ],
            ],
            'chart' => [
                'title' => 'Programme outcomes',
                'unit' => 'count',
                'points' => [
                    ['label' => 'Registered', 'value' => $registeredCount],
                    ['label' => 'Selected', 'value' => $selectedCount],
                    ['label' => 'Enrolled', 'value' => $enrolledCount],
                    ['label' => 'Jobs enabled', 'value' => (int) $this->jobs_enabled],
                    ['label' => 'Jobs created', 'value' => (int) $this->jobs_created],
                    ['label' => 'Demo hubs', 'value' => (int) $this->demo_hubs],
                    ['label' => 'Enterprises', 'value' => (int) $this->enterprises_created],
                ],
            ],
            'participants_registered' => $registered,
            'participants_selected' => $selected,
            'participants_enrolled' => $enrolled,
            'google_doc_links' => $this->normalizeLinks($this->google_doc_links),
            'image_links' => $this->normalizeImageLinks($this->image_links),
        ];
    }

    private function normalizeParticipants(?array $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                $row = is_array($row) ? $row : [];

                return [
                    'name' => (string) ($row['name'] ?? ''),
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'gender' => $row['gender'] ?? null,
                    'state' => $row['state'] ?? null,
                    'lga' => $row['lga'] ?? null,
                    'occupation' => $row['occupation'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ];
            })
            ->filter(fn ($row) => filled($row['name']) || filled($row['email']))
            ->values()
            ->all();
    }

    private function normalizeLinks(?array $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (is_string($row)) {
                    return ['title' => 'Google Doc', 'url' => $row];
                }

                $row = is_array($row) ? $row : [];

                return [
                    'title' => (string) ($row['title'] ?? 'Google Doc'),
                    'url' => (string) ($row['url'] ?? ''),
                ];
            })
            ->filter(fn ($row) => filled($row['url']))
            ->values()
            ->all();
    }

    private function normalizeImageLinks(?array $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (is_string($row)) {
                    return ['caption' => 'Activity photo', 'url' => $row];
                }

                $row = is_array($row) ? $row : [];

                return [
                    'caption' => (string) ($row['caption'] ?? $row['title'] ?? 'Activity photo'),
                    'url' => (string) ($row['url'] ?? ''),
                ];
            })
            ->filter(fn ($row) => filled($row['url']))
            ->values()
            ->all();
    }
}
