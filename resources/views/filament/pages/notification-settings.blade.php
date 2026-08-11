<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @if (filled($previewText))
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex flex-col gap-2 px-6 py-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        SMS preview
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Rendered with sample merge-tag values. This is not sent to any learner.
                    </p>
                </div>
                <div class="fi-section-content px-6 pb-6">
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm leading-relaxed text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 whitespace-pre-wrap">
                        {{ $previewText }}
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="button" color="gray" wire:click="previewSms" icon="heroicon-o-eye">
                Preview SMS
            </x-filament::button>
            <x-filament::button type="submit" icon="heroicon-o-check">
                Save settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
