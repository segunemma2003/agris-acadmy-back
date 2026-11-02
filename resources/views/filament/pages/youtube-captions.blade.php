<x-filament-panels::page>
    @php($connection = $this->getConnection())
    @php($stats = $this->getStats())

    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section class="md:col-span-1">
            <x-slot name="heading">YouTube channel</x-slot>
            @if (! $this->getConfigured())
                <p class="text-sm text-danger-600">
                    Not set up on the server yet: add <code>YOUTUBE_CLIENT_ID</code> and <code>YOUTUBE_CLIENT_SECRET</code>
                    (Google Cloud OAuth client with the YouTube Data API enabled), then press “Connect YouTube channel”.
                </p>
            @elseif ($connection)
                <p class="text-sm">
                    <span class="font-semibold text-success-600">Connected</span>
                    @if ($connection->channel_title) — {{ $connection->channel_title }} @endif
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Since {{ $connection->created_at->toFormattedDateString() }}@if ($connection->connectedBy) by {{ $connection->connectedBy->name }}@endif.
                    New lessons are checked every hour.
                </p>
            @else
                <p class="text-sm text-gray-600">
                    Not connected. Sign in with the Google account that owns Agrisiti’s YouTube channel — captions can only be downloaded for the channel’s own videos.
                </p>
            @endif
        </x-filament::section>

        <x-filament::section class="md:col-span-2">
            <x-slot name="heading">Progress</x-slot>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ([
                    ['Lesson videos', $stats['videos']],
                    ['English captions', $stats['english']],
                    ['All 7 languages', $stats['complete']],
                    ['Translating now', $stats['pending']],
                ] as [$label, $value])
                    <div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                        <div class="text-xs text-gray-500">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
            @if ($stats['missing'])
                <p class="mt-3 text-xs text-warning-600">
                    {{ $stats['missing'] }} video(s) have no captions on YouTube yet (or the video isn’t on the connected channel). YouTube adds automatic captions some time after upload; they are retried daily.
                </p>
            @endif
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
