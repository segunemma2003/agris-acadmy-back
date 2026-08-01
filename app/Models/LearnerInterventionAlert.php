<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerInterventionAlert extends Model
{
    protected $fillable = [
        'user_id',
        'reason',
        'context_key',
        'learner_name',
        'last_login_at',
        'stuck_label',
        'facilitator_id',
        'payload',
        'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'emailed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }
}
