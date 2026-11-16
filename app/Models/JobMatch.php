<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A learner matched to an employer's opening (see JobMatchingService). */
class JobMatch extends Model
{
    public const STATUSES = ['suggested', 'selected', 'accepted', 'declined', 'dismissed'];

    protected $fillable = [
        'apprenticeship_slot_id', 'user_id', 'score', 'reasons', 'status',
        'selected_at', 'responded_at', 'apprenticeship_id',
    ];

    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'score' => 'integer',
            'selected_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ApprenticeshipSlot::class, 'apprenticeship_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apprenticeship(): BelongsTo
    {
        return $this->belongsTo(Apprenticeship::class);
    }
}
