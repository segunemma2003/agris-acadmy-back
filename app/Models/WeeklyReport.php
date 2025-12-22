<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    protected $fillable = [
        'tutor_id',
        'course_id',
        'report_week_start',
        'report_week_end',
        'weekly_plan',
        'achievements',
        'activities_completed',
        'total_students',
        'active_students',
        'completed_assignments',
        'challenges',
        'next_week_plans',
        'images',
        'video_links',
        'advice',
        'status',
        'admin_feedback',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'report_week_start' => 'date',
            'report_week_end' => 'date',
            'images' => 'array',
            'video_links' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // Relationships
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
