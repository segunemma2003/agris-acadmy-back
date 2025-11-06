<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleTest extends Model
{
    protected $fillable = [
        'module_id',
        'course_id',
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
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TestQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
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

