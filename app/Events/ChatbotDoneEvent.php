<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Signals the end of a Claude streaming turn via Reverb WebSocket.
 *
 * Channel:  chatbot.{session_token}
 * Event:    done
 * Payload:  {}
 *
 * Frontend listens:
 *   channel.bind('done', () => finaliseMessage())
 */
class ChatbotDoneEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $sessionToken,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("chatbot.{$this->sessionToken}")];
    }

    public function broadcastAs(): string
    {
        return 'done';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
