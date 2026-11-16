<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PeerMessagingTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private Course $other;
    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();
        Event::fake();

        $this->tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true, 'name' => 'Tutor Tola']);
        $cat = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        $this->course = Course::create(['category_id' => $cat->id, 'tutor_id' => $this->tutor->id, 'title' => 'Rice', 'slug' => 'rice', 'description' => 'x', 'is_published' => true]);
        $this->other = Course::create(['category_id' => $cat->id, 'title' => 'Fish', 'slug' => 'fish', 'description' => 'x', 'is_published' => true]);
    }

    private function learner(string $name, ?Course $course = null, array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['role' => 'student', 'is_active' => true, 'name' => $name]);
        if ($course) {
            Enrollment::create(['user_id' => $u->id, 'course_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);
        }
        return $u;
    }

    private function send(User $from, User $to, int $courseId, string $text = 'Hello')
    {
        $this->app['auth']->forgetGuards();
        return $this->actingAs($from, 'sanctum')->postJson('/api/messages', [
            'course_id' => $courseId, 'recipient_id' => $to->id, 'subject' => 'Hi', 'message' => $text,
        ]);
    }

    public function test_classmates_can_message_each_other_but_not_strangers(): void
    {
        $ada = $this->learner('Ada', $this->course);
        $bayo = $this->learner('Bayo', $this->course);
        $stranger = $this->learner('Chidi', $this->other);

        $this->send($ada, $bayo, $this->course->id)->assertCreated();
        $this->send($ada, $stranger, $this->course->id)->assertForbidden();
        $this->send($ada, $stranger, $this->other->id)->assertForbidden();
    }

    public function test_tutor_can_reply_to_a_learner_in_their_course(): void
    {
        $ada = $this->learner('Ada', $this->course);
        $this->send($ada, $this->tutor, $this->course->id)->assertCreated();
        $this->send($this->tutor, $ada, $this->course->id)->assertCreated();
    }

    public function test_location_facilitator_messaging_still_works(): void
    {
        $ada = $this->learner('Ada', null, ['location' => 'Kano']);
        $fac = User::factory()->create(['role' => 'facilitator', 'is_active' => true, 'location' => 'kano ']);
        $far = User::factory()->create(['role' => 'facilitator', 'is_active' => true, 'location' => 'Lagos']);

        $this->send($ada, $fac, 0)->assertCreated();
        $this->send($fac, $ada, 0)->assertCreated();
        $this->send($ada, $far, 0)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $inbox = $this->actingAs($ada, 'sanctum')->getJson('/api/messages/conversations')->assertOk()->json('data');
        $this->assertSame(0, $inbox[0]['course_id']);
        $this->assertCount(2, $this->getJson("/api/messages/thread?with={$fac->id}&course_id=0")->json('data.messages'));
    }

    public function test_contacts_conversations_and_thread(): void
    {
        $ada = $this->learner('Ada', $this->course);
        $bayo = $this->learner('Bayo', $this->course);
        $this->learner('Chidi', $this->other);

        $this->actingAs($ada, 'sanctum');
        $contacts = $this->getJson('/api/messages/contacts')->assertOk()->json('data.courses');
        $this->assertCount(1, $contacts);
        $this->assertSame(['Tutor Tola'], collect($contacts[0]['tutors'])->pluck('name')->all());
        $this->assertSame(['Bayo'], collect($contacts[0]['learners'])->pluck('name')->all());
        $this->assertSame([], $this->getJson('/api/messages/contacts?search=zzz')->json('data.courses'));

        $this->send($ada, $bayo, $this->course->id, 'Did you plant yet?')->assertCreated();
        $this->send($bayo, $ada, $this->course->id, 'Yes, last week')->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->actingAs($ada, 'sanctum');
        $inbox = $this->getJson('/api/messages/conversations')->assertOk()->json('data');
        $this->assertCount(1, $inbox);
        $this->assertSame('Bayo', $inbox[0]['with']['name']);
        $this->assertSame('Yes, last week', $inbox[0]['last_message']);
        $this->assertSame(1, $inbox[0]['unread']);

        $thread = $this->getJson("/api/messages/thread?with={$bayo->id}&course_id={$this->course->id}")->assertOk()->json('data');
        $this->assertSame(['Did you plant yet?', 'Yes, last week'], collect($thread['messages'])->pluck('message')->all());
        $this->assertTrue($thread['can_reply']);
        $this->assertSame(0, $this->getJson('/api/messages/conversations')->json('data.0.unread'));
    }
}
