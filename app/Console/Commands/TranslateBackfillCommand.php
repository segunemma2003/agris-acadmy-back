<?php

namespace App\Console\Commands;

use App\Jobs\TranslateContentJob;
use App\Models\Module;
use App\Models\TestQuestion;
use App\Models\Topic;
use App\Models\TopicTestQuestion;
use Illuminate\Console\Command;

class TranslateBackfillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue background translation jobs for all existing lesson/quiz content that has not yet been translated to Hausa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targets = [
            [Module::class, ['title' => 'title_ha', 'description' => 'description_ha']],
            [Topic::class, ['title' => 'title_ha', 'write_up' => 'write_up_ha']],
            [TestQuestion::class, ['question' => 'question_ha', 'options' => 'options_ha', 'explanation' => 'explanation_ha']],
            [TopicTestQuestion::class, ['question' => 'question_ha', 'options' => 'options_ha', 'explanation' => 'explanation_ha']],
        ];

        $totalQueued = 0;

        foreach ($targets as [$modelClass, $fieldMap]) {
            $ids = $modelClass::where('is_translated_ha', false)->pluck('id');

            foreach ($ids as $id) {
                TranslateContentJob::dispatch($modelClass, $id, $fieldMap);
                $totalQueued++;
            }

            $this->info("Queued {$ids->count()} " . class_basename($modelClass) . " record(s) for translation.");
        }

        $this->info("Total: {$totalQueued} translation job(s) queued.");

        return self::SUCCESS;
    }
}
