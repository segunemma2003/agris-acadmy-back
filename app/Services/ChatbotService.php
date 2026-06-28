<?php

namespace App\Services;

use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotService
{
    public function findOrCreateSession(?string $token, ?int $userId): ChatbotSession
    {
        if ($token) {
            $session = ChatbotSession::firstOrCreate(
                ['session_token' => $token],
                ['user_id' => $userId]
            );

            // Link user_id if not already set
            if ($userId && !$session->user_id) {
                $session->update(['user_id' => $userId]);
            }
        } else {
            $session = ChatbotSession::create([
                'session_token' => (string) Str::uuid(),
                'user_id'       => $userId,
            ]);
        }

        $session->update(['last_seen_at' => now()]);

        return $session->fresh();
    }

    public function isReturningVisitor(ChatbotSession $session): bool
    {
        if (!$session->last_seen_at) {
            return false;
        }

        $hasMessages = $session->messages()->exists();
        $withinWindow = $session->last_seen_at->diffInHours(now()) < 24;

        return $hasMessages && $withinWindow;
    }

    public function getHistory(ChatbotSession $session): array
    {
        $limit = config('claude.max_history_messages', 20);

        return $session->messages()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();
    }

    public function sendToClaudeAndPersist(ChatbotSession $session, string $userContent): ChatbotMessage
    {
        // Enforce hard cap
        if ($session->messages()->count() >= 100) {
            return $session->messages()->create([
                'role'    => 'assistant',
                'content' => 'You have reached the maximum number of messages for this session. Please start a new session or contact our team directly.',
            ]);
        }

        // Persist user message
        $session->messages()->create([
            'role'    => 'user',
            'content' => $userContent,
        ]);

        // Build history including the new user message
        $history = $this->getHistory($session->fresh());

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key'         => config('claude.api_key'),
                    'anthropic-version' => config('claude.api_version'),
                    'Content-Type'      => 'application/json',
                ])
                ->post(config('claude.api_url'), [
                    'model'      => config('claude.model'),
                    'max_tokens' => config('claude.max_tokens'),
                    'system'     => $this->buildSystemPrompt(),
                    'messages'   => $history,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $replyText = $data['content'][0]['text'] ?? 'I could not generate a response. Please try again.';
                $tokenCount = $data['usage']['output_tokens'] ?? null;
            } else {
                Log::warning('Claude API non-2xx response', ['status' => $response->status()]);
                $replyText = "I'm having a little trouble right now. Please try again in a moment!";
                $tokenCount = null;
            }
        } catch (\Throwable $e) {
            Log::error('Claude API request failed', ['error' => $e->getMessage()]);
            $replyText = "I'm having a little trouble right now. Please try again in a moment!";
            $tokenCount = null;
        }

        return $session->messages()->create([
            'role'        => 'assistant',
            'content'     => $replyText,
            'token_count' => $tokenCount,
        ]);
    }

    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are Agri, a friendly AI learning assistant for Agrisiti Academy — a Nigerian online platform teaching smart farming, urban agriculture, hydroponics, livestock management, and agri-tech.

Your role:
1. Welcome visitors warmly and ask their name in the first message.
2. Guide them through 5 intake questions one at a time (occupation, State/LGA, learning goal, farming experience, preferred language — English or Hausa).
3. After each answer, acknowledge it briefly and move to the next question.
4. Once all 5 questions are answered (or user asks to skip), say: "Great! Let me find the best courses for you." — this signals the frontend to fetch recommendations.
5. Answer questions about Agrisiti Academy courses, enrollment, and features.
6. Stay focused on agriculture and Agrisiti Academy. Politely decline off-topic requests.
7. Keep every response under 120 words. Be warm, clear, and encouraging.
8. If a user wants to register, tell them to click "Register to Begin" on the course card.
9. After registration, explain that an admin will send them an enrollment code for their chosen course.

Do NOT discuss pricing, competitor platforms, or anything unrelated to Agrisiti Academy.
PROMPT;
    }
}
