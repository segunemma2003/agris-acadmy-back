<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotIntakeAnswer extends Model
{
    protected $fillable = [
        'chatbot_session_id',
        'user_id',
        'source',
        'name',
        'phone',
        'occupation',
        'state_lga',
        'learning_goal',
        'experience_level',
        'preferred_language',
        'interested_course_id',
        'skipped_questions',
    ];

    protected $casts = [
        'skipped_questions' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatbotSession::class, 'chatbot_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interestedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'interested_course_id');
    }
}
