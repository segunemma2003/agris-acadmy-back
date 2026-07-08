@php
    $notifications = $this->getNotifications();
    $unreadCount = $notifications->where('is_read', false)->count();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5 text-primary-500" />
                <span>Certificate Generation</span>
                @if ($unreadCount > 0)
                    <x-filament::badge color="success">{{ $unreadCount }} new</x-filament::badge>
                @endif
            </div>
        </x-slot>

        <div class="space-y-2">
            @forelse ($notifications as $notification)
                <div @class([
                    'flex items-start gap-3 rounded-lg border p-3',
                    'border-success-200 bg-success-50 dark:border-success-800 dark:bg-success-500/10' => ! $notification->is_read,
                    'border-gray-200 dark:border-gray-700' => $notification->is_read,
                ])>
                    <x-filament::icon
                        icon="heroicon-o-check-badge"
                        class="mt-0.5 h-5 w-5 flex-shrink-0 text-success-500"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $notification->title }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $notification->message }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No certificate batches yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
