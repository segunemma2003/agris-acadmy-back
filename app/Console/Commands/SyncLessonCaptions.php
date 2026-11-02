<?php

namespace App\Console\Commands;

use App\Jobs\SyncTopicCaptionsJob;
use App\Jobs\TranslateTopicCaptionsJob;
use App\Models\Topic;
use App\Models\TopicCaption;
use App\Services\YouTubeCaptionService;
use App\Support\CaptionLanguages;
use Illuminate\Console\Command;

class SyncLessonCaptions extends Command
{
    protected $signature = 'captions:sync {--topic=* : Only these topic ids} {--force : Re-download captions that already exist}';

    protected $description = 'Download lesson captions from the connected YouTube channel and translate them';

    public function handle(YouTubeCaptionService $youtube): int
    {
        if (! $youtube->configured() || ! $youtube->connected()) {
            $this->warn('YouTube channel not connected (Admin → YouTube captions). Nothing to do.');
            return self::SUCCESS;
        }

        $topics = Topic::query()
            ->where('is_active', true)
            ->where('video_url', 'like', '%youtu%')
            ->when($this->option('topic'), fn ($q, $ids) => $q->whereIn('id', $ids))
            ->get(['id', 'video_url']);

        $queued = 0;
        foreach ($topics as $topic) {
            $english = TopicCaption::where(['topic_id' => $topic->id, 'language' => CaptionLanguages::SOURCE])->first();
            $videoChanged = $english && $english->youtube_video_id !== CaptionLanguages::youtubeId($topic->video_url);

            // New video, changed video, earlier failure, or YouTube hadn't made captions yet (retry daily).
            if ($this->option('force') || ! $english || $videoChanged || $english->status === 'failed'
                || ($english->status === 'unavailable' && $english->updated_at->lt(now()->subDay()))) {
                SyncTopicCaptionsJob::dispatch($topic->id);
                $queued++;
                continue;
            }

            // English is there: pick up any language that is missing or failed.
            if ($english->status === 'ready') {
                $have = TopicCaption::where('topic_id', $topic->id)->whereIn('status', ['ready', 'pending'])->pluck('language')->all();
                foreach (array_diff(CaptionLanguages::targets(), $have) as $language) {
                    TopicCaption::updateOrCreate(['topic_id' => $topic->id, 'language' => $language],
                        ['status' => 'pending', 'cues' => [], 'translated_count' => 0, 'source' => 'translated', 'error' => null]);
                    TranslateTopicCaptionsJob::dispatch($topic->id, $language);
                    $queued++;
                }
            }
        }

        $this->info("Queued {$queued} caption job(s).");
        return self::SUCCESS;
    }
}
