<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A learner's work on a DIY practical: steps ticked off, observation entries, review. */
class CourseDiySubmission extends Model
{
    protected $fillable = [
        'course_diy_content_id',
        'course_id',
        'user_id',
        'steps_done',
        'entries',
        'status',
        'tutor_feedback',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'steps_done' => 'array',
            'entries' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function diy(): BelongsTo
    {
        return $this->belongsTo(CourseDiyContent::class, 'course_diy_content_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Entries with photo paths turned into public URLs, for the Academy. */
    public function entriesForDisplay(): array
    {
        $out = [];
        foreach ($this->entries ?? [] as $e) {
            $values = $e['values'] ?? [];
            foreach ($values as $k => $v) {
                if (is_string($v) && str_starts_with($v, 'diy/')) {
                    $values[$k] = Storage::disk('public')->url($v);
                }
            }
            $out[] = ['n' => (int) ($e['n'] ?? 1), 'recorded_at' => $e['recorded_at'] ?? null, 'values' => $values];
        }
        usort($out, fn ($a, $b) => $a['n'] <=> $b['n']);

        return $out;
    }

    public function toLearnerArray(): array
    {
        return [
            'status' => $this->status,
            'steps_done' => array_values($this->steps_done ?? []),
            'entries' => $this->entriesForDisplay(),
            'tutor_feedback' => $this->tutor_feedback,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
