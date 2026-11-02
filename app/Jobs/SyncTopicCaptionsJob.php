<?php

namespace App\Jobs;

use App\Models\Topic;
use App\Models\TopicCaption;
use App\Services\YouTubeCaptionService;
use App\Support\CaptionLanguages;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Downloads a lesson video's English captions from the connected YouTube
 * channel, then queues a translation into every other caption language.
 */
class SyncTopicCaptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 80;
    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public int $topicId) {}

    public function handle(YouTubeCaptionService $youtube): void
    {
        $topic = Topic::find($this->topicId);
        $videoId = CaptionLanguages::youtubeId($topic?->video_url);
        if (! $topic || ! $videoId) {
            return;
        }

        $english = TopicCaption::firstOrNew(['topic_id' => $topic->id, 'language' => CaptionLanguages::SOURCE]);
        $cues = $youtube->fetchCues($videoId);

        if (! $cues) {
            $english->fill([
                'status' => 'unavailable', 'cues' => [], 'youtube_video_id' => $videoId,
                'error' => 'This video has no captions on YouTube yet. YouTube adds automatic captions a while after upload; try again later.',
            ])->save();
            return;
        }

        $english->fill([
            'cues' => $cues, 'status' => 'ready', 'source' => 'youtube', 'error' => null,
            'youtube_video_id' => $videoId, 'translated_count' => count($cues),
        ])->save();

        $topic->forceFill([
            'transcript_english' => $english->plainText(),
            'transcription_completed' => true,
        ])->save();

        // Fresh English text → redo every translation from the start.
        foreach (CaptionLanguages::targets() as $language) {
            TopicCaption::updateOrCreate(
                ['topic_id' => $topic->id, 'language' => $language],
                ['cues' => [], 'translated_count' => 0, 'status' => 'pending', 'source' => 'translated', 'error' => null, 'youtube_video_id' => $videoId]
            );
            TranslateTopicCaptionsJob::dispatch($topic->id, $language);
        }
    }

    public function failed(\Throwable $e): void
    {
        TopicCaption::updateOrCreate(
            ['topic_id' => $this->topicId, 'language' => CaptionLanguages::SOURCE],
            ['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]
        );
    }
}
