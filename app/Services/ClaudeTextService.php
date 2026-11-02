<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One-shot Claude completions for background work (e.g. caption translation).
 * Uses the Anthropic API when ANTHROPIC_API_KEY is set, otherwise Amazon
 * Bedrock with the server's AWS credentials — same as the chatbot.
 */
class ClaudeTextService
{
    /** @return string|null  null when Claude isn't configured or the call failed (logged) */
    public function complete(string $system, string $prompt, int $maxTokens = 4096): ?string
    {
        if (filled(config('claude.api_key'))) {
            try {
                $response = Http::timeout(60)->withHeaders([
                    'x-api-key' => config('claude.api_key'),
                    'anthropic-version' => config('claude.api_version'),
                ])->post(config('claude.api_url'), [
                    'model' => config('claude.model'),
                    'max_tokens' => $maxTokens,
                    'system' => $system,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
                if ($response->successful()) {
                    return trim((string) $response->json('content.0.text')) ?: null;
                }
                Log::error('Claude request failed', ['status' => $response->status()]);
            } catch (\Throwable $e) {
                Log::error('Claude request failed', ['error' => $e->getMessage()]);
            }

            return null;
        }

        if (blank(config('services.bedrock.key')) || blank(config('services.bedrock.secret'))) {
            Log::warning('Claude not configured: no ANTHROPIC_API_KEY or AWS credentials');
            return null;
        }

        try {
            $result = $this->bedrockClient()->converse([
                'modelId' => config('services.bedrock.model_id'),
                'system' => [['text' => $system]],
                'messages' => [['role' => 'user', 'content' => [['text' => $prompt]]]],
                'inferenceConfig' => ['maxTokens' => $maxTokens],
            ])->toArray();
        } catch (\Throwable $e) {
            Log::error('Claude Bedrock request failed', ['error' => $e->getMessage()]);
            return null;
        }

        $text = trim(implode("\n", array_map(
            fn ($part) => is_array($part) ? (string) ($part['text'] ?? '') : '',
            $result['output']['message']['content'] ?? []
        )));

        return $text === '' ? null : $text;
    }

    protected function bedrockClient(): object
    {
        return new \Aws\BedrockRuntime\BedrockRuntimeClient([
            'version' => 'latest',
            'region' => config('services.bedrock.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.bedrock.key'),
                'secret' => config('services.bedrock.secret'),
            ],
            'http' => ['timeout' => 60],
        ]);
    }
}
