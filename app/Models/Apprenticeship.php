<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apprenticeship extends Model
{
    protected $fillable = [
        'apprenticeship_slot_id',
        'user_id',
        'certificate_id',
        'status',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprenticeshipLog::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Notify the organisation the moment a learner expresses interest.
        static::created(function (Apprenticeship $apprenticeship) {
            $slot = $apprenticeship->slot;
            $org = $slot?->organisation;
            $learner = $apprenticeship->user;

            if ($org && $org->user && $learner) {
                \App\Services\NotificationService::create(
                    $org->user,
                    'apprenticeship_interest',
                    'New interest in your apprenticeship slot',
                    "{$learner->name} expressed interest in '{$slot->title}'.",
                    'apprenticeship',
                    $apprenticeship->id,
                    ['slot_id' => $slot->id, 'learner_id' => $learner->id]
                );
            }
        });

        // Notify the learner when the organisation reviews their application.
        static::updated(function (Apprenticeship $apprenticeship) {
            $wasReviewed = $apprenticeship->wasChanged('status')
                && in_array($apprenticeship->status, ['accepted', 'rejected'], true);

            if ($wasReviewed && $apprenticeship->user) {
                $slot = $apprenticeship->slot;
                $verb = $apprenticeship->status === 'accepted' ? 'accepted' : 'not selected for';

                \App\Services\NotificationService::create(
                    $apprenticeship->user,
                    'apprenticeship_reviewed',
                    $apprenticeship->status === 'accepted' ? 'Apprenticeship accepted!' : 'Apprenticeship application update',
                    "You were {$verb} the '{$slot?->title}' apprenticeship at {$slot?->organisation?->name}.",
                    'apprenticeship',
                    $apprenticeship->id,
                    ['slot_id' => $slot?->id, 'status' => $apprenticeship->status]
                );
            }
        });
    }
}
