<?php

namespace App\Jobs;

use App\Models\Topic;
use App\Models\TopicCaption;
use App\Services\CaptionTranslator;
use App\Support\CaptionLanguages;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Translates a lesson's English captions into one language, a couple of
 * batches per run (each run stays well under the queue's 90s retry window)
 * and re-queues itself until the whole video is done.
 */
class TranslateTopicCaptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const BATCHES_PER_RUN = 2;

    public int $timeout = 85;
    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public int $topicId, public string $language) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("captions-{$this->topicId}-{$this->language}"))->releaseAfter(30)->expireAfter(120)];
    }

    public function handle(CaptionTranslator $translator): void
    {
        $english = TopicCaption::where(['topic_id' => $this->topicId, 'language' => CaptionLanguages::SOURCE, 'status' => 'ready'])->first();
        if (! $english || ! CaptionLanguages::isValid($this->language) || $this->language === CaptionLanguages::SOURCE) {
            return;
        }

        $caption = TopicCaption::firstOrCreate(
            ['topic_id' => $this->topicId, 'language' => $this->language],
            ['cues' => [], 'translated_count' => 0, 'status' => 'pending', 'source' => 'translated']
        );
        if ($caption->status === 'ready' || $caption->source === 'edited') {
            return; // done, or hand-corrected by an admin — don't overwrite
        }

        $source = $english->cues ?? [];
        $done = min($caption->translated_count, count($source));
        $cues = array_slice($caption->cues ?? [], 0, $done);

        for ($i = 0; $i < self::BATCHES_PER_RUN && $done < count($source); $i++) {
            $batch = array_slice($source, $done, CaptionTranslator::BATCH);
            $lines = $translator->translate(array_column($batch, 'text'), $this->language);
            if ($lines === null) {
                throw new \RuntimeException('Translation did not match the captions; will retry.');
            }
            foreach ($batch as $k => $cue) {
                $cues[] = ['start' => $cue['start'], 'end' => $cue['end'], 'text' => $lines[$k] !== '' ? $lines[$k] : $cue['text']];
            }
            $done += count($batch);
            $caption->forceFill(['cues' => $cues, 'translated_count' => $done])->save();
        }

        if ($done < count($source)) {
            self::dispatch($this->topicId, $this->language);
            return;
        }

        $caption->forceFill(['status' => 'ready', 'error' => null])->save();

        if ($this->language === 'ha') {
            Topic::whereKey($this->topicId)->update(['transcript_hausa' => $caption->plainText()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        TopicCaption::where(['topic_id' => $this->topicId, 'language' => $this->language])
            ->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
    }
}
