<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestion extends Model
{
    protected $fillable = [
        'module_test_id',
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
    public function moduleTest(): BelongsTo
    {
        return $this->belongsTo(ModuleTest::class);
    }

    // Update module test's total_questions when question is created/deleted
    protected static function boot()
    {
        parent::boot();

        static::created(function ($question) {
            $question->moduleTest->updateTotalQuestions();
        });

        static::deleted(function ($question) {
            $question->moduleTest->updateTotalQuestions();
        });
    }
}



