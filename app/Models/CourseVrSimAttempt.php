<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseVrSimAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'course_vr_content_id',
        'course_id',
        'lesson_id',
        'mode',
        'passed',
        'score',
        'correct_answers',
        'total_questions',
        'outcome',
        'actions',
        'answers',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'score' => 'integer',
            'outcome' => 'array',
            'actions' => 'array',
            'answers' => 'array',
            'summary' => 'array',
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
}
