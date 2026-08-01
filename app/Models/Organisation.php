<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'sector',
        'state',
        'lga',
        'website',
        'is_approved',
        'approved_at',
        'approved_by',
        'dashboard_permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'dashboard_permissions' => 'array',
        ];
    }

    /**
     * Whether the admin has granted this partner visibility into the given
     * dashboard section key (see config/partner_dashboard.php).
     */
    public function canView(string $section): bool
    {
        return in_array($section, $this->dashboard_permissions ?? [], true);
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
