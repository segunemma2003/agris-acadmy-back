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

    /**
     * Stream Claude's reply token-by-token as a PHP Generator.
     * Persists the user message immediately and the full assistant reply after streaming ends.
     *
     * Yields: string — each text delta from Claude
     */
    public function streamFromClaude(ChatbotSession $session, string $userContent): \Generator
    {
        if ($session->messages()->count() >= 100) {
            yield 'You have reached the maximum number of messages for this session.';
            return;
        }

        $session->messages()->create(['role' => 'user', 'content' => $userContent]);
        $history = $this->getHistory($session->fresh());

        $fullReply  = '';
        $errorReply = "I'm having a little trouble right now. Please try again in a moment!";

        if ($this->useBedrock()) {
            $reply = $this->completeViaBedrock($history)['text'] ?? null;
            $fullReply = $reply ?: $errorReply;
            // Bedrock reply arrives whole — send it in small pieces so the chat still "types".
            foreach (preg_split('/(?<=\s)/u', $fullReply) as $piece) {
                if ($piece !== '') {
                    yield $piece;
                }
            }
            $session->messages()->create(['role' => 'assistant', 'content' => $fullReply]);
            return;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key'         => config('claude.api_key'),
                    'anthropic-version' => config('claude.api_version'),
                    'Content-Type'      => 'application/json',
                ])
                ->withOptions(['stream' => true])
                ->post(config('claude.api_url'), [
                    'model'      => config('claude.model'),
                    'max_tokens' => config('claude.max_tokens'),
                    'system'     => $this->buildSystemPrompt(),
                    'messages'   => $history,
                    'stream'     => true,
                ]);

            if (!$response->successful()) {
                Log::warning('Claude streaming non-2xx', ['status' => $response->status()]);
                yield $errorReply;
                $fullReply = $errorReply;
            } else {
                $body   = $response->getBody();
                $buffer = '';

                while (!$body->eof()) {
                    $buffer .= $body->read(512);

                    // Process complete SSE lines from the buffer
                    while (($eol = strpos($buffer, "\n")) !== false) {
                        $line   = trim(substr($buffer, 0, $eol));
                        $buffer = substr($buffer, $eol + 1);

                        if (!str_starts_with($line, 'data: ')) {
                            continue;
                        }

                        $payload = substr($line, 6);
                        if ($payload === '[DONE]') {
                            break 2;
                        }

                        $parsed = json_decode($payload, true);
                        $delta  = $parsed['delta']['text'] ?? null;

                        if ($parsed['type'] === 'content_block_delta' && $delta !== null) {
                            $fullReply .= $delta;
                            yield $delta;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Claude streaming failed', ['error' => $e->getMessage()]);
            yield $errorReply;
            $fullReply = $errorReply;
        }

        // Persist the complete assistant reply
        $session->messages()->create([
            'role'    => 'assistant',
            'content' => $fullReply ?: $errorReply,
        ]);
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

        if ($this->useBedrock()) {
            $result = $this->completeViaBedrock($history);
            return $session->messages()->create([
                'role'        => 'assistant',
                'content'     => $result['text'] ?? "I'm having a little trouble right now. Please try again in a moment!",
                'token_count' => $result['tokens'] ?? null,
            ]);
        }

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

    /**
     * No Anthropic API key on the server but AWS credentials are present →
     * call Claude through Amazon Bedrock (same model family as VR Studio).
     */
    private function useBedrock(): bool
    {
        return blank(config('claude.api_key'))
            && filled(config('services.bedrock.key'))
            && filled(config('services.bedrock.secret'));
    }

    /** Separate so tests can swap in a fake client. */
    protected function bedrockClient(): object
    {
        return new \Aws\BedrockRuntime\BedrockRuntimeClient([
            'version' => 'latest',
            'region' => config('services.bedrock.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.bedrock.key'),
                'secret' => config('services.bedrock.secret'),
            ],
            'http' => ['timeout' => 30],
        ]);
    }

    /**
     * One Claude reply via Bedrock's Converse API.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{text: string, tokens: ?int}|null  null on failure (logged)
     */
    private function completeViaBedrock(array $history): ?array
    {
        // Converse needs the conversation to start with the user and alternate roles.
        $messages = [];
        foreach ($history as $m) {
            $role = $m['role'] === 'assistant' ? 'assistant' : 'user';
            $text = trim((string) $m['content']);
            if ($text === '' || ($messages === [] && $role !== 'user')) {
                continue;
            }
            $last = count($messages) - 1;
            if ($last >= 0 && $messages[$last]['role'] === $role) {
                $messages[$last]['content'][0]['text'] .= "\n\n".$text;
            } else {
                $messages[] = ['role' => $role, 'content' => [['text' => $text]]];
            }
        }
        if ($messages === []) {
            return null;
        }

        try {
            $result = $this->bedrockClient()->converse([
                'modelId' => config('services.bedrock.model_id'),
                'system' => [['text' => $this->buildSystemPrompt()]],
                'messages' => $messages,
                'inferenceConfig' => ['maxTokens' => (int) config('claude.max_tokens', 1024)],
            ])->toArray();
        } catch (\Throwable $e) {
            Log::error('Chatbot Bedrock request failed', ['error' => $e->getMessage()]);
            return null;
        }

        $text = trim(implode("\n", array_map(
            fn ($part) => is_array($part) ? (string) ($part['text'] ?? '') : '',
            $result['output']['message']['content'] ?? []
        )));

        return $text === '' ? null : ['text' => $text, 'tokens' => $result['usage']['outputTokens'] ?? null];
    }
}
