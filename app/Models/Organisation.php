<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    /** What kind of organisation is looking for talent. */
    public const TYPES = [
        'farm' => 'Farm',
        'agro_enterprise' => 'Agro-processing / agribusiness',
        'recruitment_agency' => 'Recruitment agency',
        'institution' => 'Research / training institution',
        'ngo' => 'NGO / development programme',
        'government' => 'Government agency',
        'other' => 'Other',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'sector',
        'organisation_type',
        'country',
        'state',
        'lga',
        'city',
        'website',
        'phone',
        'talent_needs',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            // Kept for transitional partner-dashboard accounts created before the partner role split.
            'dashboard_permissions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ApprenticeshipSlot::class);
    }
}
