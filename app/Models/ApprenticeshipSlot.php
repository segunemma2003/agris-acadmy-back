<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprenticeshipSlot extends Model
{
    protected $fillable = [
        'organisation_id',
        'title',
        'description',
        'sector',
        'state',
        'lga',
        'duration',
        'required_course_id',
        'openings',
        'application_deadline',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'application_deadline' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'required_course_id');
    }

    public function apprenticeships(): HasMany
    {
        return $this->hasMany(Apprenticeship::class);
    }
}
