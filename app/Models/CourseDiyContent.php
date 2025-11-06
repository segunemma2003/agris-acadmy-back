<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDiyContent extends Model
{
    protected $table = 'course_diy_content';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'instructions',
        'materials_needed',
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
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}



