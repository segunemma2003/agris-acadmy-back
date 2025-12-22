<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffOnboardingVideoView extends Model
{
    protected $fillable = [
        'user_id',
        'video_id',
        'watched_at',
        'watch_duration',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
            'watch_duration' => 'integer',
            'is_completed' => 'boolean',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
