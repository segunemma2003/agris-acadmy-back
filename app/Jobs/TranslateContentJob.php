<?php

namespace App\Jobs;

use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Translates a model's English fields into Hausa in the background, whether
 * triggered by new content being created or existing content being backfilled.
 */
class TranslateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120];
    public $timeout = 60;

    /**
     * @param string $modelClass Fully qualified model class, e.g. App\Models\Topic
     * @param int $modelId
     * @param array<string,string> $fieldMap Source field => target "_ha" field
     */
    public function __construct(
        public string $modelClass,
        public int $modelId,
        public array $fieldMap,
    ) {}

    public function handle(TranslationService $translator): void
    {
        $model = $this->modelClass::find($this->modelId);
        if (!$model) {
            return;
        }

        $updates = [];
        foreach ($this->fieldMap as $sourceField => $targetField) {
            $sourceValue = $model->{$sourceField};

            if (empty($sourceValue)) {
                continue;
            }

            if (is_array($sourceValue)) {
                $updates[$targetField] = $translator->translateArrayToHausa($sourceValue);
            } else {
                $translated = $translator->translateToHausa((string) $sourceValue);
                if ($translated !== null) {
                    $updates[$targetField] = $translated;
                }
            }
        }

        if (empty($updates)) {
            Log::warning('TranslateContentJob produced no translations', [
                'model' => $this->modelClass,
                'id' => $this->modelId,
            ]);
            return;
        }

        $updates['is_translated_ha'] = true;
        $model->updateQuietly($updates);
    }
}
