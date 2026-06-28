<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast one streamed token from Claude to the frontend via Reverb WebSocket.
 *
 * Channel:  chatbot.{session_token}   (public — token itself is the secret)
 * Event:    chunk
 * Payload:  { delta: string }
 *
 * Frontend listens:
 *   channel.bind('chunk', (data) => appendToLastMessage(data.delta))
 */
class ChatbotChunkEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $sessionToken,
        public readonly string $delta,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("chatbot.{$this->sessionToken}")];
    }

    public function broadcastAs(): string
    {
        return 'chunk';
    }

    public function broadcastWith(): array
    {
        return ['delta' => $this->delta];
    }
}
