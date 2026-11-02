<?php

namespace App\Services;

use App\Models\YoutubeConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Downloads timed captions for lesson videos from Agrisiti's own YouTube
 * channel (YouTube Data API captions.download — only works for the channel's
 * own videos, which is why an admin connects the channel once).
 */
class YouTubeCaptionService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API = 'https://www.googleapis.com/youtube/v3';
    private const SCOPE = 'https://www.googleapis.com/auth/youtube.force-ssl';

    public function configured(): bool
    {
        return filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret'));
    }

    public function connected(): bool
    {
        return YoutubeConnection::query()->exists();
    }

    public function redirectUri(): string
    {
        return route('admin.youtube.callback');
    }

    public function authUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.youtube.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** Swap the OAuth code for tokens and remember the channel. */
    public function connect(string $code, ?int $userId): YoutubeConnection
    {
        $tokens = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        if (empty($tokens['refresh_token'])) {
            throw new \RuntimeException('Google did not return a refresh token — remove the app from the Google account’s third-party access and connect again.');
        }

        $channel = Http::withToken($tokens['access_token'])
            ->get(self::API.'/channels', ['part' => 'snippet', 'mine' => 'true'])
            ->json('items.0');

        YoutubeConnection::query()->delete();
        Cache::put($this->tokenCacheKey(), $tokens['access_token'], now()->addSeconds(max(60, ($tokens['expires_in'] ?? 3600) - 300)));

        return YoutubeConnection::create([
            'channel_id' => $channel['id'] ?? null,
            'channel_title' => $channel['snippet']['title'] ?? null,
            'refresh_token' => $tokens['refresh_token'],
            'connected_by' => $userId,
        ]);
    }

    public function disconnect(): void
    {
        YoutubeConnection::query()->delete();
        Cache::forget($this->tokenCacheKey());
    }

    /**
     * English (or the video's main language) timed captions for one video.
     *
     * @return array<int, array{start: float, end: float, text: string}>|null  null when the video has no captions
     */
    public function fetchCues(string $videoId): ?array
    {
        $tracks = $this->api()->get(self::API.'/captions', ['part' => 'snippet', 'videoId' => $videoId])
            ->throw()->json('items') ?? [];
        $track = $this->pickTrack($tracks);
        if (! $track) {
            return null;
        }

        $srt = $this->api()->accept('*/*')->get(self::API.'/captions/'.$track['id'], ['tfmt' => 'srt'])->throw()->body();

        return self::mergeCues(self::parseSrt($srt));
    }

    /** Prefer human English captions, then YouTube's automatic English ones, then anything. */
    private function pickTrack(array $tracks): ?array
    {
        $score = function (array $t) {
            $s = $t['snippet'] ?? [];
            $english = str_starts_with(strtolower($s['language'] ?? ''), 'en');
            $auto = ($s['trackKind'] ?? '') === 'asr';
            return ($english ? 2 : 0) + ($auto ? 0 : 1);
        };
        usort($tracks, fn ($a, $b) => $score($b) <=> $score($a));

        return $tracks[0] ?? null;
    }

    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(30)->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), now()->addMinutes(50), function () {
            $connection = YoutubeConnection::current();
            if (! $connection) {
                throw new \RuntimeException('YouTube channel is not connected');
            }

            return Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ])->throw()->json('access_token');
        });
    }

    private function tokenCacheKey(): string
    {
        return 'youtube-captions-access-token';
    }

    /** @return array<int, array{start: float, end: float, text: string}> */
    public static function parseSrt(string $srt): array
    {
        $cues = [];
        $blocks = preg_split("/\r?\n\s*\r?\n/", trim(str_replace("\xEF\xBB\xBF", '', $srt)));
        foreach ($blocks as $block) {
            $lines = preg_split("/\r?\n/", trim($block));
            foreach ($lines as $i => $line) {
                if (preg_match('/(\d+):(\d+):(\d+)[,.](\d+)\s*-->\s*(\d+):(\d+):(\d+)[,.](\d+)/', $line, $m)) {
                    $text = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode(implode(' ', array_slice($lines, $i + 1))))));
                    $text = trim(preg_replace('/\[(music|applause|laughter)\]/i', '', $text));
                    if ($text !== '') {
                        $cues[] = [
                            'start' => $m[1] * 3600 + $m[2] * 60 + $m[3] + $m[4] / 1000,
                            'end' => $m[5] * 3600 + $m[6] * 60 + $m[7] + $m[8] / 1000,
                            'text' => $text,
                        ];
                    }
                    break;
                }
            }
        }

        // Automatic captions can repeat the previous line as they roll — keep only the new words.
        $clean = [];
        foreach ($cues as $cue) {
            $prev = end($clean);
            if ($prev && $cue['text'] === $prev['text']) {
                $clean[count($clean) - 1]['end'] = max($prev['end'], $cue['end']);
                continue;
            }
            if ($prev && str_starts_with($cue['text'], $prev['text'].' ')) {
                $cue['text'] = trim(substr($cue['text'], strlen($prev['text'])));
            }
            $clean[] = $cue;
        }

        return $clean;
    }

    /**
     * Join short fragments into readable captions (≤ 2 lines on screen),
     * which also gives translators whole phrases instead of word fragments.
     *
     * @return array<int, array{start: float, end: float, text: string}>
     */
    public static function mergeCues(array $cues, float $maxSeconds = 7.0, int $maxChars = 110): array
    {
        $out = [];
        foreach ($cues as $cue) {
            $last = count($out) - 1;
            if ($last >= 0) {
                $prev = $out[$last];
                $endsSentence = preg_match('/[.?!]["\')]?$/', $prev['text']);
                if (! $endsSentence
                    && $cue['end'] - $prev['start'] <= $maxSeconds
                    && mb_strlen($prev['text'].' '.$cue['text']) <= $maxChars) {
                    $out[$last]['text'] = $prev['text'].' '.$cue['text'];
                    $out[$last]['end'] = max($prev['end'], $cue['end']);
                    continue;
                }
            }
            $out[] = ['start' => round($cue['start'], 2), 'end' => round($cue['end'], 2), 'text' => $cue['text']];
        }

        // Rolling auto-captions overlap; stop each line when the next begins.
        foreach ($out as $i => $c) {
            if (isset($out[$i + 1]) && $c['end'] > $out[$i + 1]['start']) {
                $out[$i]['end'] = $out[$i + 1]['start'];
            }
        }

        return $out;
    }
}
