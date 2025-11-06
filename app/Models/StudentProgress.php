<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'topic_id',
        'is_completed',
        'watch_time_seconds',
        'completion_percentage',
        'started_at',
        'completed_at',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completion_percentage' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
