<?php

namespace Tests\Feature;

use App\Filament\Pages\YouTubeCaptions;
use App\Filament\Resources\TopicResource;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\RelationManagers\CaptionsRelationManager;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Topic;
use App\Models\TopicCaption;
use App\Models\User;
use App\Models\YoutubeConnection;
use App\Services\ClaudeTextService;
use App\Services\YouTubeCaptionService;
use Filament\Facades\Filament;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lesson captions: English timed captions come from Agrisiti's YouTube
 * channel, every other language is translated by Claude, and learners
 * switch language from the player.
 */
class LessonCaptionsTest extends TestCase
{
    use RefreshDatabase;

    public array $translationCalls = [];

    private const SRT = "1\n00:00:00,000 --> 00:00:02,500\nWelcome to poultry\n\n2\n00:00:02,000 --> 00:00:04,000\nhousing basics.\n\n3\n00:00:04,000 --> 00:00:07,000\n[Music]\n\n4\n00:00:07,000 --> 00:00:10,000\nGood ventilation keeps birds healthy.\n";

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.env' => 'local',
            'services.youtube.client_id' => 'cid',
            'services.youtube.client_secret' => 'secret',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at', 'expires_in' => 3600]),
            'www.googleapis.com/youtube/v3/captions/*' => Http::response(self::SRT),
            'www.googleapis.com/youtube/v3/captions?*' => Http::response(['items' => [
                ['id' => 'auto', 'snippet' => ['language' => 'en', 'trackKind' => 'asr']],
                ['id' => 'human', 'snippet' => ['language' => 'en', 'trackKind' => 'standard']],
            ]]),
        ]);

        // Claude stand-in: prefixes each line with the language name.
        $test = $this;
        $this->app->bind(ClaudeTextService::class, fn () => new class($test) extends ClaudeTextService {
            public function __construct(private $test) {}
            public function complete(string $system, string $prompt, int $maxTokens = 4096): ?string
            {
                preg_match('/into (\w+)\./', $system, $m);
                $this->test->translationCalls[] = $m[1];
                return json_encode(array_map(fn ($l) => "[{$m[1]}] {$l}", json_decode($prompt, true)));
            }
        });

        YoutubeConnection::create(['refresh_token' => 'rt', 'channel_title' => 'Agrisiti']);
    }

    private function lesson(): Topic
    {
        $category = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        $course = Course::create(['category_id' => $category->id, 'title' => 'Poultry', 'slug' => 'poultry', 'description' => 'x', 'is_published' => true]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Housing', 'sort_order' => 1, 'is_active' => true]);

        return Topic::create(['module_id' => $module->id, 'title' => 'Ventilation', 'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'is_active' => true, 'sort_order' => 1]);
    }

    public function test_srt_is_cleaned_and_merged_into_readable_captions(): void
    {
        $cues = YouTubeCaptionService::mergeCues(YouTubeCaptionService::parseSrt(self::SRT));
        $this->assertSame(['Welcome to poultry housing basics.', 'Good ventilation keeps birds healthy.'], array_column($cues, 'text'));
        $this->assertEquals(0.0, $cues[0]['start']);
        $this->assertEquals(4.0, $cues[0]['end']);
    }

    public function test_learner_gets_captions_and_switches_language(): void
    {
        $topic = $this->lesson();
        $learner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $outsider = User::factory()->create(['role' => 'student', 'is_active' => true]);
        Enrollment::create(['user_id' => $learner->id, 'course_id' => $topic->module->course_id, 'status' => 'active', 'enrolled_at' => now()]);

        $this->actingAs($outsider, 'sanctum')->getJson("/api/topics/{$topic->id}/captions")->assertForbidden();

        // First request queues the YouTube fetch + translations and says "pending"…
        $this->actingAs($learner, 'sanctum')->getJson("/api/topics/{$topic->id}/captions?language=en")
            ->assertOk()->assertJsonPath('data.status', 'pending');
        // …the player polls and gets them (the queue runs inline in tests).
        $res = $this->getJson("/api/topics/{$topic->id}/captions?language=en")->assertOk();
        $res->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.cues.0.text', 'Welcome to poultry housing basics.')
            ->assertJsonCount(7, 'data.languages');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/captions/human')); // human track preferred over automatic

        $this->getJson("/api/topics/{$topic->id}/captions?language=ha")
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.cues.1.text', '[Hausa] Good ventilation keeps birds healthy.')
            ->assertJsonPath('data.cues.1.start', 7);
        $this->getJson("/api/topics/{$topic->id}/captions?language=pt")->assertJsonPath('data.cues.0.text', '[Portuguese] Welcome to poultry housing basics.');

        $this->assertEqualsCanonicalizing(['Hausa', 'Yoruba', 'Igbo', 'French', 'Portuguese', 'Spanish'], $this->translationCalls);
        $this->assertSame('[Hausa] Welcome to poultry housing basics. [Hausa] Good ventilation keeps birds healthy.', $topic->fresh()->transcript_hausa);
    }

    public function test_admin_page_and_hand_edits(): void
    {
        $topic = $this->lesson();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->artisan('captions:sync')->assertSuccessful();
        $this->assertSame(7, $topic->captions()->where('status', 'ready')->count());

        $this->get(YouTubeCaptions::getUrl())->assertOk()->assertSee('Agrisiti');
        Livewire::test(YouTubeCaptions::class)->assertCanSeeTableRecords([$topic])->assertSee('7/7');

        $hausa = $topic->captions()->where('language', 'ha')->first();
        $text = CaptionsRelationManager::toText($hausa->cues);
        Livewire::test(CaptionsRelationManager::class, ['ownerRecord' => $topic, 'pageClass' => EditTopic::class])
            ->callTableAction(EditAction::class, $hausa, data: ['cues_text' => str_replace('[Hausa] Good', 'Iska mai kyau', $text)])
            ->assertHasNoTableActionErrors();
        $hausa->refresh();
        $this->assertSame('edited', $hausa->source);
        $this->assertStringStartsWith('Iska mai kyau', $hausa->cues[1]['text']);

        // A re-run of the sync leaves the edited language alone.
        $this->artisan('captions:sync')->assertSuccessful();
        $this->assertSame('edited', $hausa->fresh()->source);
        $this->get(TopicResource::getUrl('edit', ['record' => $topic]))->assertOk();
    }
}
