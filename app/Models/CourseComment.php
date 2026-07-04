<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseComment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'module_id',
        'comment',
        'parent_id',
        'is_pinned',
        'is_accepted',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_accepted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CourseComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CourseComment::class, 'parent_id');
    }
}
