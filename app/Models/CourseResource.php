<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseResource extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'resource_type',
        'external_url',
        'is_free',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
