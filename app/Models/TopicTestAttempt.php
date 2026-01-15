<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicTestAttempt extends Model
{
    protected $fillable = [
        'topic_test_id',
        'user_id',
        'answers',
        'score',
        'total_questions',
        'percentage',
        'is_passed',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'percentage' => 'decimal:2',
            'is_passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function topicTest(): BelongsTo
    {
        return $this->belongsTo(TopicTest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
