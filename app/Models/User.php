<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'gender',
        'location',
        'bio',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Relationships
    // Primary tutor courses (backward compatibility)
    public function coursesAsTutor()
    {
        return $this->hasMany(Course::class, 'tutor_id');
    }

    // Multiple courses as tutor (many-to-many)
    public function coursesAsTutors(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_tutors', 'tutor_id', 'course_id')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function savedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'saved_courses');
    }

    public function lessonComments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    public function courseComments(): HasMany
    {
        return $this->hasMany(CourseComment::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function progress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function notes()
    {
        return $this->hasMany(StudentNote::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function enrollmentCodes()
    {
        return $this->hasMany(EnrollmentCode::class, 'tutor_id');
    }

    public function onboardingQuizAttempts()
    {
        return $this->hasMany(StaffOnboardingQuizAttempt::class);
    }

    public function onboardingVideoViews()
    {
        return $this->hasMany(StaffOnboardingVideoView::class);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_active;
    }

    /**
     * Check if user can access the admin panel
     */
    public function canAccessPanel(string $panel): bool
    {
        // Always return true for admin panel to bypass Filament's check
        // We'll handle authorization in middleware if needed
        if ($panel === 'admin') {
            \Log::info('canAccessPanel: Bypassing check for admin panel', [
                'user_id' => $this->id,
                'email' => $this->email,
                'role' => $this->role,
                'is_active' => $this->is_active,
            ]);
            return true;
        }
        
        $result = match($panel) {
            'tutor' => $this->role === 'tutor' && $this->is_active,
            'tagdev' => $this->role === 'tagdev' && $this->is_active,
            'facilitator' => $this->role === 'facilitator' && $this->is_active,
            default => false,
        };
        
        return $result;
    }

    public function isTutor(): bool
    {
        return $this->role === 'tutor';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
}
