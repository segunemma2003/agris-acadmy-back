<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to both sender and recipient's private channels
        return [
            new PrivateChannel('private-course.' . $this->message->course_id . '.user.' . $this->message->sender_id),
            new PrivateChannel('private-course.' . $this->message->course_id . '.user.' . $this->message->recipient_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message-sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'course_id' => $this->message->course_id,
            'sender_id' => $this->message->sender_id,
            'recipient_id' => $this->message->recipient_id,
            'subject' => $this->message->subject,
            'message' => $this->message->message,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
            ],
            'recipient' => [
                'id' => $this->message->recipient->id,
                'name' => $this->message->recipient->name,
                'avatar' => $this->message->recipient->avatar,
            ],
        ];
    }
}
