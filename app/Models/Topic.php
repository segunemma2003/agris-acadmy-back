<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'module_id',
        'tutor_id',
        'title',
        'description',
        'video_url',
        'transcript',
        'transcript_english',
        'transcript_hausa',
        'transcription_completed',
        'write_up',
        'duration_minutes',
        'content_type',
        'is_free',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'transcription_completed' => 'boolean',
        ];
    }

    // Relationships
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(TopicDownload::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function test(): HasMany
    {
        return $this->hasMany(TopicTest::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    // Update module's total_topics when topic is created/deleted
    protected static function boot()
    {
        parent::boot();

        static::created(function ($topic) {
            $topic->module->updateTotalTopics();
        });

        static::deleted(function ($topic) {
            $topic->module->updateTotalTopics();
        });
    }
}
