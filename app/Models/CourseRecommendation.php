<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRecommendation extends Model
{
    protected $fillable = [
        'course_id',
        'recommended_course_id',
        'sort_order',
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function recommendedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'recommended_course_id');
    }
}



