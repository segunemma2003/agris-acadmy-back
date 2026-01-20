<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'course_id',
        'tutor_id',
        'title',
        'description',
        'total_topics',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sort_order');
    }

    public function test(): HasMany
    {
        return $this->hasMany(ModuleTest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    // Update total_topics when topics are added/removed
    // Send email notifications when a new module is added
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($module) {
            $module->updateTotalTopics();
        });

        // Send email notification when a new module is created and is active
        static::created(function ($module) {
            if ($module->is_active) {
                $module->notifyEnrolledStudents();
            }
        });

        // Send email notification when a module is activated
        static::updated(function ($module) {
            if ($module->is_active && $module->wasChanged('is_active') && !$module->getOriginal('is_active')) {
                $module->notifyEnrolledStudents();
            }
        });
    }

    /**
     * Notify all enrolled students about the new module
     */
    public function notifyEnrolledStudents(): void
    {
        try {
            $course = $this->course;
            if (!$course) {
                return;
            }

            // Get all active enrollments for this course
            $enrollments = \App\Models\Enrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($enrollments as $enrollment) {
                if ($enrollment->user && $enrollment->user->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($enrollment->user->email)
                            ->queue(new \App\Mail\NewModuleNotificationMail(
                                $enrollment->user,
                                $course,
                                $this
                            ));
                    } catch (\Exception $e) {
                        \Log::error('Failed to queue new module notification email', [
                            'user_id' => $enrollment->user->id,
                            'module_id' => $this->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to notify enrolled students about new module', [
                'module_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateTotalTopics(): void
    {
        $this->total_topics = $this->topics()->count();
        $this->saveQuietly();
    }
}
