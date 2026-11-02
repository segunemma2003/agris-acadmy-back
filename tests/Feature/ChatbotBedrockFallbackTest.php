<?php

namespace Tests\Feature;

use App\Services\ChatbotService;
use Aws\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * With no Anthropic API key but AWS credentials configured (as on the
 * servers), the chatbot answers through Amazon Bedrock instead of always
 * replying "I'm having a little trouble right now".
 */
class ChatbotBedrockFallbackTest extends TestCase
{
    use RefreshDatabase;

    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'claude.api_key' => null,
            'services.bedrock.key' => 'test-key',
            'services.bedrock.secret' => 'test-secret',
            'services.bedrock.model_id' => 'us.anthropic.claude-haiku-4-5-20251001-v1:0',
        ]);

        $test = $this;
        $this->app->bind(ChatbotService::class, fn () => new class($test) extends ChatbotService {
            public function __construct(private $test) {}

            protected function bedrockClient(): object
            {
                $test = $this->test;
                return new class($test) {
                    public function __construct(private $test) {}

                    public function converse(array $args): Result
                    {
                        $this->test->sent[] = $args;
                        return new Result([
                            'output' => ['message' => ['role' => 'assistant', 'content' => [['text' => 'Try the Poultry Basics course.']]]],
                            'usage' => ['outputTokens' => 7],
                        ]);
                    }
                };
            }
        });
    }

    public function test_plain_and_streamed_replies_come_from_bedrock(): void
    {
        $token = $this->postJson('/api/chatbot/session', ['session_token' => null])->json('data.session_token');

        $this->postJson('/api/chatbot/message', ['session_token' => $token, 'content' => 'I keep chickens in Kano'])
            ->assertOk()
            ->assertJsonPath('data.message.content', 'Try the Poultry Basics course.');

        $stream = $this->post('/api/chatbot/message/stream', ['session_token' => $token, 'content' => 'And feed?'], ['Accept' => 'text/event-stream']);
        $stream->assertOk();
        $body = $stream->streamedContent();
        $this->assertStringContainsString('Poultry', $body);
        $this->assertStringNotContainsString('having a little trouble', $body);

        // Conversation sent to Bedrock starts with the user and alternates roles.
        $last = end($this->sent);
        $this->assertSame('us.anthropic.claude-haiku-4-5-20251001-v1:0', $last['modelId']);
        $this->assertSame('user', $last['messages'][0]['role']);
        $system = $last['system'][0]['text'];
        $this->assertStringContainsString("I'm only an agritech assistant", $system);
        $this->assertStringContainsString('NEVER give external links', $system);
        foreach (array_slice($last['messages'], 1) as $i => $m) {
            $this->assertNotSame($last['messages'][$i]['role'], $m['role']);
        }
    }
}
