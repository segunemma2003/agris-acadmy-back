<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicDownload extends Model
{
    protected $fillable = [
        'user_id',
        'topic_id',
        'course_resource_id',
        'downloaded_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function courseResource(): BelongsTo
    {
        return $this->belongsTo(CourseResource::class);
    }
}
