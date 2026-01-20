<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopicTest extends Model
{
    protected $fillable = [
        'topic_id',
        'module_id',
        'course_id',
        'tutor_id',
        'title',
        'description',
        'passing_score',
        'time_limit_minutes',
        'total_questions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TopicTestQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TopicTestAttempt::class);
    }

    // Update total_questions when questions are added/removed
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($test) {
            $test->updateTotalQuestions();
        });
    }

    public function updateTotalQuestions(): void
    {
        $this->total_questions = $this->questions()->count();
        $this->saveQuietly();
    }
}
