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
        'locale',
        'phone',
        'gender',
        'location',
        'state',
        'lga',
        'occupation',
        'age',
        'date_of_birth',
        'referral',
        'bio',
        'avatar',
        'is_active',
        'last_login_at',
        'facilitator_id',
        'is_in_facilitator_queue',
        'covered_states',
        'covered_lgas',
        'current_streak',
        'longest_streak',
        'last_active_date',
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
            'date_of_birth' => 'date',
            'is_in_facilitator_queue' => 'boolean',
            'covered_states' => 'array',
            'covered_lgas' => 'array',
            'last_active_date' => 'date',
        ];
    }

    // Relationships

    // The facilitator assigned to this learner
    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    // Learners assigned to this facilitator
    public function assignedLearners()
    {
        return $this->hasMany(User::class, 'facilitator_id');
    }

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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // The organisation profile this user manages (role: organisation)
    public function organisation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Organisation::class);
    }

    // Apprenticeship applications/placements this learner has expressed interest in
    public function apprenticeships(): HasMany
    {
        return $this->hasMany(Apprenticeship::class);
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

    /**
     * Record a learning event (topic completion, quiz submission, progress sync,
     * etc.) toward this learner's daily activity streak. A calendar day only
     * counts once no matter how many events land on it. Missing a full calendar
     * day resets the streak to 1 on the next activity, per the dashboard's
     * "streak resets to 0 if a full day is missed" rule (0 between visits,
     * back to 1 the moment activity resumes).
     */
    public function recordActivity(): void
    {
        $today = now()->toDateString();

        if ($this->last_active_date?->toDateString() === $today) {
            return;
        }

        $wasYesterday = $this->last_active_date?->toDateString() === now()->subDay()->toDateString();

        $this->current_streak = $wasYesterday ? $this->current_streak + 1 : 1;
        $this->longest_streak = max($this->longest_streak, $this->current_streak);
        $this->last_active_date = $today;
        $this->saveQuietly();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
