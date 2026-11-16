<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected static function booted(): void
    {
        // A new certificate belongs on the learner's CV
        static::saved(function (Certificate $certificate) {
            if ($certificate->wasRecentlyCreated || $certificate->wasChanged(['certificate_number', 'issued_date'])) {
                \App\Jobs\BuildLearnerCvJob::dispatch((int) $certificate->user_id)->afterCommit();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_id',
        'certificate_template_id',
        'certificate_number',
        'recipient_name',
        'issued_date',
        'file_path',
        'graduate_funding_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'graduate_funding_notified_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    // Generate unique certificate number
    public static function generateCertificateNumber(): string
    {
        do {
            $number = 'CERT-' . strtoupper(Str::random(16));
        } while (self::where('certificate_number', $number)->exists());

        return $number;
    }
}



