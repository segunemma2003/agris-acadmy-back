<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseVrCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'course_vr_content_id',
        'course_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vrContent(): BelongsTo
    {
        return $this->belongsTo(CourseVrContent::class, 'course_vr_content_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
