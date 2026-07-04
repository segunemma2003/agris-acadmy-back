<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestion extends Model
{
    protected $fillable = [
        'module_test_id',
        'question',
        'question_ha',
        'image',
        'question_type',
        'options',
        'options_ha',
        'correct_answer',
        'explanation',
        'explanation_ha',
        'is_translated_ha',
        'points',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'options_ha' => 'array',
            'is_translated_ha' => 'boolean',
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

        // Auto-translate to Hausa in the background whenever the source text changes
        static::saved(function ($question) {
            if ($question->wasChanged('question') || $question->wasChanged('options') || $question->wasChanged('explanation') || $question->wasRecentlyCreated) {
                \App\Jobs\TranslateContentJob::dispatch(static::class, $question->id, [
                    'question' => 'question_ha',
                    'options' => 'options_ha',
                    'explanation' => 'explanation_ha',
                ]);
            }
        });
    }
}
