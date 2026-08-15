<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseVrContent extends Model
{
    protected $table = 'course_vr_content';

    protected $fillable = [
        'course_id',
        'module_id',
        'tutor_id',
        'title',
        'description',
        'instructions',
        'cta_label',
        'vr_url',
        'studio_slug',
        'scene_json',
        'studio_status',
        'published_at',
        'thumbnail',
        'duration_minutes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'scene_json' => 'array',
            'published_at' => 'datetime',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}

