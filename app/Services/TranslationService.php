<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Translate a single string to Hausa via the Google Translate API.
     * Returns null if translation isn't configured or fails, so callers can
     * leave the record untranslated rather than storing garbage.
     */
    public function translateToHausa(string $text): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        $apiKey = env('GOOGLE_TRANSLATE_API_KEY');
        if (!$apiKey) {
            Log::warning('Translation skipped: GOOGLE_TRANSLATE_API_KEY is not configured');
            return null;
        }

        try {
            $response = Http::timeout(30)->post('https://translation.googleapis.com/language/translate/v2', [
                'key' => $apiKey,
                'q' => $text,
                'source' => 'en',
                'target' => 'ha',
                'format' => 'text',
            ]);

            if (!$response->successful()) {
                Log::error('Google Translate API error: ' . $response->body());
                return null;
            }

            return $response->json('data.translations.0.translatedText');
        } catch (\Throwable $e) {
            Log::error('Translation request failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Translate every string value in an array (e.g. quiz answer options),
     * preserving keys. Non-string values are passed through untouched.
     */
    public function translateArrayToHausa(array $values): array
    {
        $translated = [];
        foreach ($values as $key => $value) {
            $translated[$key] = is_string($value) ? ($this->translateToHausa($value) ?? $value) : $value;
        }
        return $translated;
    }
}
