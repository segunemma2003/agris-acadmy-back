<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTopicCaptionsJob;
use App\Jobs\TranslateTopicCaptionsJob;
use App\Models\Topic;
use App\Models\TopicCaption;
use App\Services\YouTubeCaptionService;
use App\Support\CaptionLanguages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicCaptionController extends Controller
{
    /**
     * GET /topics/{topic}/captions?language=ha
     *
     * Timed captions for a lesson video in one language, plus the status of
     * every language so the player can show the language menu. Asking for a
     * language that isn't ready yet queues it, so it fills in within minutes.
     */
    public function show(Request $request, Topic $topic, YouTubeCaptionService $youtube): JsonResponse
    {
        $user = $request->user();
        $courseId = $topic->module?->course_id;
        $allowed = $topic->is_free
            || in_array($user->role, ['admin', 'tutor', 'facilitator'], true)
            || $user->enrollments()->where('course_id', $courseId)->exists();
        if (! $allowed) {
            return response()->json(['success' => false, 'message' => 'You are not enrolled in this course'], 403);
        }

        $language = $request->query('language', CaptionLanguages::SOURCE);
        if (! CaptionLanguages::isValid($language)) {
            $language = CaptionLanguages::SOURCE;
        }

        $captions = TopicCaption::where('topic_id', $topic->id)->get()->keyBy('language');
        $english = $captions->get(CaptionLanguages::SOURCE);
        $hasVideo = (bool) CaptionLanguages::youtubeId($topic->video_url);

        // Nothing yet: fetch from YouTube now instead of waiting for the hourly sync.
        if (! $english && $hasVideo && $youtube->configured() && $youtube->connected()) {
            $english = TopicCaption::create(['topic_id' => $topic->id, 'language' => CaptionLanguages::SOURCE, 'status' => 'pending', 'cues' => []]);
            $captions->put(CaptionLanguages::SOURCE, $english);
            SyncTopicCaptionsJob::dispatch($topic->id);
        }

        // English ready but this language missing or failed: translate it now.
        $wanted = $captions->get($language);
        if ($english?->status === 'ready' && $language !== CaptionLanguages::SOURCE
            && (! $wanted || $wanted->status === 'failed')) {
            $wanted = TopicCaption::updateOrCreate(['topic_id' => $topic->id, 'language' => $language],
                ['status' => 'pending', 'cues' => [], 'translated_count' => 0, 'source' => 'translated', 'error' => null]);
            $captions->put($language, $wanted);
            TranslateTopicCaptionsJob::dispatch($topic->id, $language);
        }

        $languages = collect(CaptionLanguages::ALL)->map(function ($label, $code) use ($captions, $english) {
            $c = $captions->get($code);
            $status = $c?->status ?? ($english?->status === 'ready' ? 'available' : 'missing');
            return [
                'code' => $code,
                'label' => $label,
                'status' => $status, // ready | pending | available (translated on request) | failed | unavailable | missing
                'progress' => $c && $status === 'pending' && $english?->cues
                    ? (int) round(100 * min($c->translated_count, count($english->cues)) / count($english->cues))
                    : null,
            ];
        })->values();

        $selected = $captions->get($language);

        return response()->json([
            'success' => true,
            'data' => [
                'topic_id' => $topic->id,
                'language' => $language,
                'status' => $selected?->status ?? ($hasVideo ? 'missing' : 'no_video'),
                'cues' => $selected?->status === 'ready' ? ($selected->cues ?? []) : [],
                'languages' => $languages,
            ],
        ]);
    }
}
