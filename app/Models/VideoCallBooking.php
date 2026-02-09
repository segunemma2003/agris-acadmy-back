<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallBooking extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'course_id',
        'type',
        'status',
        'scheduled_at',
        'duration_minutes',
        'extension_minutes',
        'is_extended',
        'started_at',
        'ended_at',
        'notes',
        'meeting_link',
        'meeting_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_extended' => 'boolean',
        ];
    }

    // Valid slot durations
    const VALID_DURATIONS = [5, 10, 15];
    
    // Valid extension durations
    const VALID_EXTENSIONS = [5, 10];
    
    // Buffer time between bookings (in minutes)
    const BUFFER_MINUTES = 20;

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // Helper methods
    public function getTotalDurationMinutes(): int
    {
        return $this->duration_minutes + $this->extension_minutes;
    }

    public function getEndTime(): \Carbon\Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->getTotalDurationMinutes());
    }

    public function canExtend(): bool
    {
        return !$this->is_extended && in_array($this->status, ['confirmed', 'in_progress']);
    }
}
