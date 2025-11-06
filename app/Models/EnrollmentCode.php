<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EnrollmentCode extends Model
{
    protected $fillable = [
        'course_id',
        'tutor_id',
        'user_id',
        'code',
        'is_used',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Generate unique code
    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}

