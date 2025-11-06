<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'total_topics',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sort_order');
    }

    public function test(): HasMany
    {
        return $this->hasMany(ModuleTest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    // Update total_topics when topics are added/removed
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($module) {
            $module->updateTotalTopics();
        });
    }

    public function updateTotalTopics(): void
    {
        $this->total_topics = $this->topics()->count();
        $this->saveQuietly();
    }
}
