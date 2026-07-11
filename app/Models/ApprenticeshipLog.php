<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprenticeshipLog extends Model
{
    protected $fillable = [
        'apprenticeship_id',
        'log_date',
        'attended',
        'activity_description',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'attended' => 'boolean',
        ];
    }

    public function apprenticeship(): BelongsTo
    {
        return $this->belongsTo(Apprenticeship::class);
    }
}
