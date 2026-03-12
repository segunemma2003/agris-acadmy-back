<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id',
        'user_id',
        'submission_content',
        'file_path',
        'file_name',
        'status',
        'score',
        'feedback',
        'submitted_at',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    // Relationships
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessor for file URL
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // If it's already a full URL, return as is
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        // Otherwise, return the storage URL
        return asset('storage/' . $this->file_path);
    }

    /**
     * Boot method to create notifications on assignment submission events
     */
    protected static function boot()
    {
        parent::boot();

        // Create notification when assignment is graded
        static::updated(function ($submission) {
            // Check if assignment was just graded (status changed to graded or returned)
            $wasGraded = in_array($submission->status, ['graded', 'returned']) && 
                        $submission->wasChanged('status') &&
                        !in_array($submission->getOriginal('status'), ['graded', 'returned']);

            if ($wasGraded) {
                $user = $submission->user;
                $assignment = $submission->assignment;
                $course = $assignment ? $assignment->course : null;

                if ($user && $assignment && $course) {
                    $scoreText = $submission->score !== null ? "Score: {$submission->score}" : "";
                    \App\Services\NotificationService::create(
                        $user,
                        'assignment_graded',
                        'Assignment Graded',
                        "Your assignment '{$assignment->title}' for '{$course->title}' has been graded. {$scoreText}",
                        'assignment',
                        $assignment->id,
                        [
                            'course_id' => $course->id,
                            'course_title' => $course->title,
                            'assignment_id' => $assignment->id,
                            'assignment_title' => $assignment->title,
                            'submission_id' => $submission->id,
                            'score' => $submission->score,
                            'status' => $submission->status,
                            'feedback' => $submission->feedback,
                        ]
                    );
                }
            }
        });
    }
}



