<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TranscribeVideos extends Command
{
    protected $signature = 'videos:transcribe';
    protected $description = 'Transcribe videos for lessons in Hausa and English';

    public function handle()
    {
        $this->info('Starting video transcription...');

        // Check if API key is configured
        if (empty(env('ASSEMBLYAI_API_KEY')) && empty(env('DEEPGRAM_API_KEY'))) {
            $this->warn('No transcription API key configured.');
            $this->info('Set ASSEMBLYAI_API_KEY or DEEPGRAM_API_KEY in .env to enable transcription.');
            $this->info('Skipping transcription. Videos will be transcribed when API key is configured.');
            return 0;
        }

        // Get topics with videos that haven't been transcribed
        $topics = Topic::whereNotNull('video_url')
            ->where('transcription_completed', false)
            ->where('is_active', true)
            ->limit(10) // Process 10 at a time
            ->get();

        if ($topics->isEmpty()) {
            $this->info('No videos to transcribe.');
            return 0;
        }

        $processed = 0;
        $successful = 0;
        $failed = 0;

        foreach ($topics as $topic) {
            try {
                $this->info("Processing topic: {$topic->title} (ID: {$topic->id})");
                $this->info("Video URL: {$topic->video_url}");

                // Transcribe in English
                $this->info("Transcribing in English...");
                $englishTranscript = $this->transcribeVideo($topic->video_url, 'en');
                
                if (empty($englishTranscript)) {
                    $this->warn("English transcription failed for topic {$topic->id}");
                    $failed++;
                    continue;
                }

                // Transcribe in Hausa (or translate if service doesn't support Hausa)
                $this->info("Transcribing in Hausa...");
                $hausaTranscript = $this->transcribeVideo($topic->video_url, 'ha');
                
                // If Hausa transcription failed, try translating English to Hausa
                if (empty($hausaTranscript) && !empty($englishTranscript)) {
                    $this->info("Hausa transcription not available, translating from English...");
                    $hausaTranscript = $this->translateText($englishTranscript, 'en', 'ha');
                }

                $topic->update([
                    'transcript_english' => $englishTranscript,
                    'transcript_hausa' => $hausaTranscript ?: null,
                    'transcription_completed' => true,
                ]);

                $this->info("✓ Successfully transcribed topic {$topic->id}");
                $this->info("  English: " . substr($englishTranscript, 0, 100) . "...");
                if ($hausaTranscript) {
                    $this->info("  Hausa: " . substr($hausaTranscript, 0, 100) . "...");
                }
                $successful++;
                $processed++;
            } catch (\Exception $e) {
                $this->error("Failed to transcribe topic {$topic->id}: " . $e->getMessage());
                Log::error("Video transcription failed for topic {$topic->id}: " . $e->getMessage());
                $failed++;
                $processed++;
            }
        }

        $this->info("\n=== Transcription Summary ===");
        $this->info("Processed: {$processed}");
        $this->info("Successful: {$successful}");
        $this->info("Failed: {$failed}");
        
        return 0;
    }

    /**
     * Transcribe video using available service
     */
    private function transcribeVideo(string $videoUrl, string $language): string
    {
        // Try AssemblyAI first (easiest to use)
        if (env('ASSEMBLYAI_API_KEY')) {
            return $this->transcribeWithAssemblyAI($videoUrl, $language);
        }

        // Try Deepgram
        if (env('DEEPGRAM_API_KEY')) {
            return $this->transcribeWithDeepgram($videoUrl, $language);
        }

        return '';
    }

    /**
     * Transcribe using AssemblyAI
     */
    private function transcribeWithAssemblyAI(string $videoUrl, string $language): string
    {
        try {
            $apiKey = env('ASSEMBLYAI_API_KEY');
            
            // Submit transcription job
            $response = Http::timeout(30)->withHeaders([
                'authorization' => $apiKey,
                'content-type' => 'application/json',
            ])->post('https://api.assemblyai.com/v2/transcript', [
                'audio_url' => $videoUrl,
                'language_code' => $language === 'ha' ? 'ha' : 'en',
                'punctuate' => true,
                'format_text' => true,
            ]);

            if (!$response->successful()) {
                $error = $response->json();
                throw new \Exception("AssemblyAI API error: " . ($error['error'] ?? $response->body()));
            }

            $data = $response->json();
            $transcriptId = $data['id'] ?? null;

            if (!$transcriptId) {
                throw new \Exception("No transcript ID returned from AssemblyAI");
            }

            $this->info("  Transcription job submitted: {$transcriptId}");

            // Poll for completion (max 10 minutes)
            $maxAttempts = 120; // 10 minutes max (5 second intervals)
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;

                $statusResponse = Http::timeout(30)->withHeaders([
                    'authorization' => $apiKey,
                ])->get("https://api.assemblyai.com/v2/transcript/{$transcriptId}");

                if (!$statusResponse->successful()) {
                    throw new \Exception("Failed to check transcription status");
                }

                $statusData = $statusResponse->json();
                $status = $statusData['status'] ?? 'unknown';

                if ($status === 'completed') {
                    $transcript = $statusData['text'] ?? '';
                    if (!empty($transcript)) {
                        return $transcript;
                    }
                    throw new \Exception("Transcription completed but no text returned");
                }

                if ($status === 'error') {
                    $errorMsg = $statusData['error'] ?? 'Unknown error';
                    throw new \Exception("Transcription failed: {$errorMsg}");
                }

                // Still processing
                if ($attempt % 12 === 0) { // Log every minute
                    $this->info("  Still processing... ({$attempt * 5}s elapsed)");
                }
            }

            throw new \Exception("Transcription timed out after 10 minutes");
        } catch (\Exception $e) {
            Log::error("AssemblyAI transcription error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transcribe using Deepgram
     */
    private function transcribeWithDeepgram(string $videoUrl, string $language): string
    {
        try {
            $apiKey = env('DEEPGRAM_API_KEY');
            
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Token ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.deepgram.com/v1/listen', [
                'url' => $videoUrl,
                'language' => $language === 'ha' ? 'ha' : 'en-US',
                'punctuate' => true,
                'model' => 'nova-2',
            ]);

            if (!$response->successful()) {
                $error = $response->json();
                throw new \Exception("Deepgram API error: " . ($error['err_msg'] ?? $response->body()));
            }

            $data = $response->json();
            
            if (isset($data['results']['channels'][0]['alternatives'][0]['transcript'])) {
                return $data['results']['channels'][0]['alternatives'][0]['transcript'];
            }

            throw new \Exception("No transcript returned from Deepgram");
        } catch (\Exception $e) {
            Log::error("Deepgram transcription error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Translate text from one language to another
     */
    private function translateText(string $text, string $fromLang, string $toLang): string
    {
        // Try Google Translate API
        if (env('GOOGLE_TRANSLATE_API_KEY')) {
            return $this->translateWithGoogle($text, $fromLang, $toLang);
        }

        // Try AWS Translate (if AWS SDK is available)
        if (env('AWS_ACCESS_KEY_ID') && env('AWS_DEFAULT_REGION')) {
            return $this->translateWithAWS($text, $fromLang, $toLang);
        }

        return '';
    }

    /**
     * Translate using Google Translate API
     */
    private function translateWithGoogle(string $text, string $fromLang, string $toLang): string
    {
        try {
            $apiKey = env('GOOGLE_TRANSLATE_API_KEY');
            
            // Google Translate API v2
            $response = Http::timeout(30)->post('https://translation.googleapis.com/language/translate/v2', [
                'key' => $apiKey,
                'q' => $text,
                'source' => $fromLang,
                'target' => $toLang === 'ha' ? 'ha' : 'en',
                'format' => 'text',
            ]);

            if (!$response->successful()) {
                throw new \Exception("Google Translate API error: " . $response->body());
            }

            $data = $response->json();
            
            if (isset($data['data']['translations'][0]['translatedText'])) {
                return $data['data']['translations'][0]['translatedText'];
            }

            return '';
        } catch (\Exception $e) {
            Log::error("Google Translate error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Translate using AWS Translate
     */
    private function translateWithAWS(string $text, string $fromLang, string $toLang): string
    {
        try {
            // AWS Translate requires AWS SDK
            if (!class_exists('\Aws\Translate\TranslateClient')) {
                $this->warn("AWS Translate SDK not installed. Install with: composer require aws/aws-sdk-php");
                return '';
            }

            $translateClient = new \Aws\Translate\TranslateClient([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            $result = $translateClient->translateText([
                'Text' => $text,
                'SourceLanguageCode' => $fromLang,
                'TargetLanguageCode' => $toLang === 'ha' ? 'ha' : 'en',
            ]);

            return $result['TranslatedText'] ?? '';
        } catch (\Exception $e) {
            Log::error("AWS Translate error: " . $e->getMessage());
            return '';
        }
    }
}
