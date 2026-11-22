<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseDiyContent extends Model
{
    protected $table = 'course_diy_content';

    protected $fillable = [
        'course_id',
        'tutor_id',
        'title',
        'description',
        'instructions',
        'materials_needed',
        'steps',
        'observation_fields',
        'entries_required',
        'entry_label',
        'requires_review',
        'video_url',
        'image',
        'estimated_time_minutes',
        'difficulty_level',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'materials_needed' => 'array',
            'steps' => 'array',
            'observation_fields' => 'array',
            'entries_required' => 'integer',
            'requires_review' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CourseDiySubmission::class, 'course_diy_content_id');
    }

    /** Observation fields with a stable key for each (tutors only type the question). */
    public function fields(): array
    {
        $out = [];
        foreach ($this->observation_fields ?? [] as $i => $f) {
            $label = trim((string) ($f['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $out[] = [
                'key' => $f['key'] ?? ('f'.$i),
                'label' => $label,
                'type' => in_array($f['type'] ?? 'text', ['text', 'number', 'choice', 'photo', 'check'], true) ? $f['type'] : 'text',
                'unit' => $f['unit'] ?? null,
                'options' => array_values(array_filter(array_map('trim', is_array($f['options'] ?? null) ? $f['options'] : explode(',', (string) ($f['options'] ?? ''))))),
                'required' => (bool) ($f['required'] ?? false),
                'help' => $f['help'] ?? null,
            ];
        }

        return $out;
    }
}
