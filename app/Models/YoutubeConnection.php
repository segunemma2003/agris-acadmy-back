<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeConnection extends Model
{
    protected $fillable = ['channel_id', 'channel_title', 'refresh_token', 'connected_by'];

    protected $hidden = ['refresh_token'];

    protected function casts(): array
    {
        return ['refresh_token' => 'encrypted'];
    }

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }
}
