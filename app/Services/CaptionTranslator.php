<?php

namespace App\Services;

use App\Support\CaptionLanguages;

/** Translates caption lines with Claude, keeping one translated line per original line. */
class CaptionTranslator
{
    public const BATCH = 40;

    public function __construct(private ClaudeTextService $claude) {}

    /**
     * @param  string[]  $lines
     * @return string[]|null  same length and order, or null if the reply never matched
     * @throws \RuntimeException when Claude can't be reached
     */
    public function translate(array $lines, string $language): ?array
    {
        $lines = array_values($lines);
        if ($lines === []) {
            return [];
        }

        $out = $this->translateBatch($lines, $language);
        if ($out !== null) {
            return $out;
        }

        // The model returned the wrong number of lines — split and try smaller pieces.
        if (count($lines) === 1) {
            return null;
        }
        $half = intdiv(count($lines), 2);
        $a = $this->translate(array_slice($lines, 0, $half), $language);
        $b = $a === null ? null : $this->translate(array_slice($lines, $half), $language);

        return $b === null ? null : array_merge($a, $b);
    }

    private function translateBatch(array $lines, string $language): ?array
    {
        $name = CaptionLanguages::ENGLISH_NAMES[$language] ?? $language;
        $count = count($lines);

        $system = "You translate video captions for Agrisiti Academy, a Nigerian agricultural learning platform, from English into {$name}. "
            ."Write natural, simple spoken {$name} that farmers understand. Keep agricultural terms accurate; keep names, numbers, units and product names. "
            ."Each input line is one caption shown on screen while the video plays: translate each line on its own, keeping the same order. "
            ."Reply with ONLY a JSON array of exactly {$count} strings — no notes, no markdown.";

        $reply = $this->claude->complete($system, json_encode($lines, JSON_UNESCAPED_UNICODE), 8000);
        if ($reply === null) {
            // Claude unreachable — splitting won't help, let the job retry later.
            throw new \RuntimeException('Translation service unavailable');
        }

        $start = strpos($reply, '[');
        $end = strrpos($reply, ']');
        if ($start === false || $end === false) {
            return null;
        }
        $decoded = json_decode(substr($reply, $start, $end - $start + 1), true);
        if (! is_array($decoded) || count($decoded) !== $count) {
            return null;
        }

        return array_map(fn ($t) => trim((string) $t), array_values($decoded));
    }
}
