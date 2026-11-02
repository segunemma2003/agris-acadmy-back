<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The "what do you want to learn about?" answer picks matching courses, and the bot is told only real courses. */
class ChatbotRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_interest_drives_recommendations_and_prompt_lists_courses(): void
    {
        $category = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        foreach (['Rice Farming Basics', 'Catfish Pond Management', 'Hydroponic Lettuce'] as $title) {
            Course::create(['category_id' => $category->id, 'title' => $title, 'slug' => str($title)->slug(), 'description' => 'Intro', 'is_published' => true]);
        }

        $token = $this->postJson('/api/chatbot/session', ['session_token' => null])->json('data.session_token');
        $this->postJson('/api/chatbot/answers', [
            'session_token' => $token, 'interest' => 'Fish farming', 'learning_goal' => 'Start a farm',
            'skipped_questions' => [6],
        ])->assertOk();

        $recs = $this->postJson('/api/chatbot/recommendations', ['session_token' => $token])->assertOk()->json('data.recommendations');
        $this->assertSame('Catfish Pond Management', $recs[0]['course']['title']);
        $this->assertStringContainsString('fish farming', $recs[0]['course']['rationale']);

        $prompt = app(ChatbotService::class)->buildSystemPrompt();
        $this->assertStringContainsString('Hydroponic Lettuce', $prompt);
        $this->assertMatchesRegularExpression('#/course/\d+#', $prompt);
    }
}
