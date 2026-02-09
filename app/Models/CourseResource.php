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

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // If it's already a full URL, return as is
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        // Otherwise, return the storage URL
        return asset('storage/' . $this->file_path);
    }

    // Boot method to handle file upload metadata (fallback)
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($resource) {
            // If file_path is set and file_name is not, extract from file_path
            if ($resource->file_path && !$resource->file_name) {
                $resource->file_name = basename($resource->file_path);
            }

            // If file_path is set and metadata is missing, try to get file info
            if ($resource->file_path && file_exists(storage_path('app/public/' . $resource->file_path))) {
                $fullPath = storage_path('app/public/' . $resource->file_path);
                
                if (!$resource->file_size) {
                    $resource->file_size = filesize($fullPath);
                }
                
                if (!$resource->file_type) {
                    $resource->file_type = mime_content_type($fullPath) ?: 'application/octet-stream';
                }
            }
        });
    }
}
