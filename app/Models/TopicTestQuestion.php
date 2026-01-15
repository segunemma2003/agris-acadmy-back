<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicTestQuestion extends Model
{
    protected $fillable = [
        'topic_test_id',
        'question',
        'question_type',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    // Relationships
    public function topicTest(): BelongsTo
    {
        return $this->belongsTo(TopicTest::class);
    }

    // Update topic test's total_questions when question is created/deleted
    protected static function boot()
    {
        parent::boot();

        static::created(function ($question) {
            $question->topicTest->updateTotalQuestions();
        });

        static::deleted(function ($question) {
            $question->topicTest->updateTotalQuestions();
        });
    }
}
