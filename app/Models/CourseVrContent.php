<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function simAttempts(): HasMany
    {
        return $this->hasMany(CourseVrSimAttempt::class, 'course_vr_content_id');
    }

    /** VR Studio simulation lesson (e.g. fish pond) rather than a 360° scene. */
    public function isSimulation(): bool
    {
        $scene = $this->scene_json;
        if (is_string($scene)) {
            $scene = json_decode($scene, true);
        }

        return is_array($scene) && str_starts_with((string) ($scene['engine'] ?? ''), 'sim-');
    }
}

