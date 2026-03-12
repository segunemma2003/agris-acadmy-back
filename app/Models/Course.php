<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    protected $fillable = [
        'category_id',
        'tutor_id',
        'title',
        'slug',
        'short_description',
        'description',
        'about',
        'requirements',
        'what_to_expect',
        'what_you_will_learn',
        'what_you_will_get',
        'image',
        'preview_video_url',
        'materials_count',
        'lessons_count',
        'tags',
        'rating',
        'rating_count',
        'enrollment_count',
        'price',
        'is_free',
        'is_published',
        'is_featured',
        'certificate_included',
        'duration_minutes',
        'level',
        'language',
        'course_information',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'what_you_will_learn' => 'array',
            'what_you_will_get' => 'array',
            'course_information' => 'array',
            'rating' => 'decimal:2',
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'certificate_included' => 'boolean',
        ];
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Primary tutor (backward compatibility)
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    // Multiple tutors (many-to-many)
    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_tutors', 'course_id', 'tutor_id')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CourseResource::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function vrContent(): HasMany
    {
        return $this->hasMany(CourseVrContent::class)->orderBy('sort_order');
    }

    public function diyContent(): HasMany
    {
        return $this->hasMany(CourseDiyContent::class)->orderBy('sort_order');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(CourseRecommendation::class, 'course_id');
    }

    public function recommendedCourses(): HasMany
    {
        return $this->hasMany(CourseRecommendation::class, 'recommended_course_id');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_courses');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CourseComment::class);
    }

    // Scope for tutors to see accessible courses
    public function scopeAccessibleByTutor($query, $tutorId)
    {
        return $query->where(function ($q) use ($tutorId) {
            // Primary tutor
            $q->where('tutor_id', $tutorId)
              // Additional tutor
              ->orWhereHas('tutors', fn ($query) => $query->where('tutor_id', $tutorId))
              // Course created by admin
              ->orWhereHas('tutor', fn ($query) => $query->where('role', 'admin'));
        });
    }

    // Auto-generate slug and create notifications
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });

        // Create notification when a new course is published
        static::created(function ($course) {
            if ($course->is_published) {
                // Notify all students about new course
                \App\Services\NotificationService::createForRole(
                    'student',
                    'course_added',
                    'New Course Available',
                    "A new course '{$course->title}' has been added and is now available for enrollment.",
                    'course',
                    $course->id,
                    [
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'course_slug' => $course->slug,
                        'category_id' => $course->category_id,
                    ]
                );
            }
        });

        // Create notification when a course is published (status changed)
        static::updated(function ($course) {
            if ($course->is_published && $course->wasChanged('is_published') && !$course->getOriginal('is_published')) {
                // Notify all students when course is published
                \App\Services\NotificationService::createForRole(
                    'student',
                    'course_published',
                    'New Course Published',
                    "The course '{$course->title}' has been published and is now available for enrollment.",
                    'course',
                    $course->id,
                    [
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'course_slug' => $course->slug,
                        'category_id' => $course->category_id,
                    ]
                );
            }
        });
    }
}
