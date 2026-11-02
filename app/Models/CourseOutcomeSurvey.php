<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Part 5 end-of-course check — one per enrollment per stage (completion, six_month). */
class CourseOutcomeSurvey extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'enrollment_id', 'stage',
        'completed_course', 'work_change', 'started_enterprise', 'feels_respected', 'income_band',
        'rating', 'used_learning', 'would_recommend', 'improvement',
        'declaration_name', 'declaration_date',
    ];

    protected function casts(): array
    {
        return [
            'completed_course' => 'boolean',
            'started_enterprise' => 'boolean',
            'would_recommend' => 'boolean',
            'declaration_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
