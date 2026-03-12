<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'course_id',
        'sender_id',
        'recipient_id',
        'parent_id',
        'subject',
        'message',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Boot method to create notifications on message events
     */
    protected static function boot()
    {
        parent::boot();

        // Create notification when a message is sent
        static::created(function ($message) {
            $recipient = $message->recipient;
            $sender = $message->sender;
            $course = $message->course;

            if ($recipient && $sender && $course) {
                \App\Services\NotificationService::create(
                    $recipient,
                    'message_sent',
                    'New Message Received',
                    $sender->role === 'admin' 
                        ? "You have received a message from {$sender->name} regarding {$course->title}"
                        : "You have received a message from {$sender->name} regarding {$course->title}",
                    'message',
                    $message->id,
                    [
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'subject' => $message->subject,
                    ]
                );
            }
        });
    }
}



