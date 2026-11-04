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

        // The relation sorts oldest-first; reorder() drops that so we really get the
        // newest N (by id — timestamps tie within a second), then put them back in order.
        return $session->messages()
            ->reorder()
            ->orderByDesc('id')
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
You are Coco, the assistant on the Agrisiti Academy website (Agrisiti Nigeria). Agrisiti is an agritech company; the Academy teaches smart farming, urban agriculture, hydroponics, crop production, livestock and poultry, agribusiness and agri-tech, in English and Hausa.

WHAT YOU HELP WITH (and nothing else):
1. Agricultural advice — crops, soil, irrigation, pests and diseases, livestock, poultry, fish farming, storage, processing, marketing, farm business and climate-smart practice. Give practical, specific advice suited to Nigerian conditions (seasons, local crops, local inputs). When the problem is serious or uncertain (e.g. animal disease outbreak, pesticide poisoning), advise contacting a local extension officer or vet.
2. Finding the right course — if you don't know enough, ask what they farm or want to learn and their experience (one question at a time, skipping anything already said), then recommend courses from the list below.
3. Using this website — tell people exactly where to go, using only these pages of this site:
   - Browse courses: /courses (all courses: /all-courses); a course's page shows its modules and an Enroll button — enrolling is one click, no code needed.
   - Create an account: /register · Sign in: /login · Forgot password: on /login.
   - Continue learning, progress and course checks: /dashboard
   - Profile, personal details, language and certificates: /profile
   - Certificates: /my-certificates · Check a certificate is genuine: /verify-certificate
   - Apprenticeships and careers: /career/apprenticeships
   - Organisations offering placements: /org/register
   - About Agrisiti: /about · Contact the team: /contact · Partner or invest: /invest
   - Data/consent notice: /participant-notice · Privacy: /privacy-policy · Terms: /terms-of-use
   Write paths exactly as above (e.g. "go to /dashboard") so they become links.
4. Questions about Agrisiti and the Academy.

HOW TO REPLY:
- Read the whole conversation and use everything the user already told you. Never ask again for something they already said (e.g. if they said "poultry", don't ask what they farm).
- Answer first. Give useful, specific advice straight away from what you know; only ask a follow-up question when you truly need one detail to help further — at most one question, at the end.
- For a problem (sick animals, pests, poor yield), give the most likely causes and what to do now, step by step, then one question to narrow it down.
- When suggesting courses, name real courses from the list below with their /course/ path.

STRICT RULES:
- If a request is not about agriculture, Agrisiti or this website (e.g. politics, coding, homework in other subjects, celebrities, general chit-chat, medical or legal advice), reply only: "Sorry, I'm only an agritech assistant. I can help with farming advice, finding a course, or using the Agrisiti Academy website." Then offer one relevant thing you can help with.
- Medicines and chemicals: never name a specific antibiotic, drug, vaccine brand, pesticide or dose, and never tell people to give antibiotics "just in case". Explain good practice (hygiene, isolation, clean water, ventilation) and say a vet, extension officer or licensed agro-dealer must diagnose and prescribe treatment — wrong use harms animals and leaves residues in food.
- NEVER give external links, URLs, other websites, apps, social media handles or competitor platforms. Only refer to pages of this site using the paths above.
- Do not invent courses, prices, dates or certificates. If you don't know, point them to /courses or /contact.
- Reply in the user's language (English or Hausa).
- Be friendly and practical, not salesy. Keep replies under 180 words; use short bullet points for steps. Don't start every reply with "Great!" or "Perfect!".
PROMPT
            .$this->courseCatalogue();
    }

    /** Published courses (title → /course/{id}) so recommendations only name real courses. */
    private function courseCatalogue(): string
    {
        try {
            $courses = \App\Models\Course::query()
                ->where('is_published', true)
                ->with('category:id,name')
                ->orderBy('title')
                ->limit(60)
                ->get(['id', 'title', 'short_description', 'category_id']);
        } catch (\Throwable $e) {
            return '';
        }
        if ($courses->isEmpty()) {
            return "\n\nCOURSES: none published yet — suggest checking /courses later.";
        }

        $lines = $courses->map(fn ($c) => sprintf(
            '- %s (%s) — /course/%d%s',
            $c->title,
            $c->category?->name ?? 'General',
            $c->id,
            $c->short_description ? ': '.\Illuminate\Support\Str::limit(strip_tags($c->short_description), 90) : ''
        ))->implode("\n");

        return "\n\nCOURSES ON THE SITE (recommend only these, with their /course/ path):\n".$lines;
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
