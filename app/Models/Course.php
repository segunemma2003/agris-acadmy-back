<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'what_you_will_learn',
        'what_you_will_get',
        'image',
        'materials_count',
        'tags',
        'rating',
        'rating_count',
        'enrollment_count',
        'price',
        'is_free',
        'is_published',
        'is_featured',
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
        ];
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
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

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }
}
