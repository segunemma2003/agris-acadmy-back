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
        'resolved_at',
        'resolved_by',
        'auto_resolved',
        'resolution_note',
    ];

    /** Why a learner was flagged — shown in the panels, emails and partner counts. */
    public const REASONS = [
        'inactive_7d' => 'Inactive on the platform',
        'quiz_failed_twice' => 'Failed the same quiz twice',
        'stalled_course' => 'Stalled partway through a course',
        'never_started' => 'Enrolled but never started',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'emailed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'auto_resolved' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }
}
