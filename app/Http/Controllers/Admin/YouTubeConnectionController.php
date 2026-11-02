<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTopicCaptionsJob;
use App\Models\Topic;
use App\Services\YouTubeCaptionService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admin connects Agrisiti's YouTube channel (Google OAuth) so lesson captions can be downloaded. */
class YouTubeConnectionController extends Controller
{
    private function back(): RedirectResponse
    {
        return redirect()->route('filament.admin.pages.youtube-captions');
    }

    public function connect(Request $request, YouTubeCaptionService $youtube): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if (! $youtube->configured()) {
            Notification::make()->title('Add YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET to the server first')->danger()->send();
            return $this->back();
        }

        $state = Str::random(40);
        $request->session()->put('youtube_oauth_state', $state);

        return redirect()->away($youtube->authUrl($state));
    }

    public function callback(Request $request, YouTubeCaptionService $youtube): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $expected = $request->session()->pull('youtube_oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            Notification::make()->title('YouTube connection expired — please try again')->danger()->send();
            return $this->back();
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            Notification::make()->title('YouTube connection was cancelled')->warning()->send();
            return $this->back();
        }

        try {
            $connection = $youtube->connect($request->query('code'), $request->user()->id);
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Could not connect YouTube')->body($e->getMessage())->danger()->send();
            return $this->back();
        }

        // Start fetching captions for every lesson video right away.
        Topic::query()->where('is_active', true)->where('video_url', 'like', '%youtu%')->pluck('id')
            ->each(fn ($id) => SyncTopicCaptionsJob::dispatch($id));

        Notification::make()
            ->title('YouTube connected'.($connection->channel_title ? ": {$connection->channel_title}" : ''))
            ->body('Captions are being downloaded and translated in the background.')
            ->success()->send();

        return $this->back();
    }
}
