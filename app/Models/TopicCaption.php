<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicCaption extends Model
{
    protected $fillable = [
        'topic_id', 'language', 'cues', 'translated_count', 'source', 'status', 'error', 'youtube_video_id',
    ];

    protected function casts(): array
    {
        return ['cues' => 'array', 'translated_count' => 'integer'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** Plain transcript text (for search, downloads and the old transcript fields). */
    public function plainText(): string
    {
        return collect($this->cues ?? [])->pluck('text')->implode(' ');
    }
}
