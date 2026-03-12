<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_code',
        'status',
        'enrolled_at',
        'completed_at',
        'progress_percentage',
        'amount_paid',
        'payment_method',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'progress_percentage' => 'decimal:2',
            'amount_paid' => 'decimal:2',
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

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /**
     * Boot method to create notifications on enrollment events
     */
    protected static function boot()
    {
        parent::boot();

        // Create notification when user enrolls in a course
        static::created(function ($enrollment) {
            $user = $enrollment->user;
            $course = $enrollment->course;

            if ($user && $course) {
                \App\Services\NotificationService::create(
                    $user,
                    'enrollment_confirmed',
                    'Enrollment Confirmed',
                    "You have successfully enrolled in '{$course->title}'. Start learning now!",
                    'enrollment',
                    $enrollment->id,
                    [
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'course_slug' => $course->slug,
                        'enrollment_id' => $enrollment->id,
                    ]
                );
            }
        });

        // Create notification when course is completed
        static::updated(function ($enrollment) {
            if ($enrollment->status === 'completed' && $enrollment->wasChanged('status')) {
                $user = $enrollment->user;
                $course = $enrollment->course;

                if ($user && $course) {
                    \App\Services\NotificationService::create(
                        $user,
                        'course_completed',
                        'Course Completed! 🎉',
                        "Congratulations! You have completed '{$course->title}'. " . 
                        ($course->certificate_included ? "Your certificate is available." : ""),
                        'enrollment',
                        $enrollment->id,
                        [
                            'course_id' => $course->id,
                            'course_title' => $course->title,
                            'course_slug' => $course->slug,
                            'enrollment_id' => $enrollment->id,
                            'certificate_included' => $course->certificate_included,
                        ]
                    );
                }
            }
        });
    }
}
