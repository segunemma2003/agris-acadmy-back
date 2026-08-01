<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Filters</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Heat map data refreshes daily
                        @if($cacheGeneratedAt)
                            · last snapshot {{ \Illuminate\Support\Carbon::parse($cacheGeneratedAt)->timezone(config('app.timezone'))->format('d M Y H:i') }}
                        @else
                            · <span class="text-amber-600 dark:text-amber-400">no snapshot yet — ask an admin to rebuild cache</span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    @php $counts = $this->bandCounts(); @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">Red {{ $counts['red'] }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Amber {{ $counts['amber'] }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200">Teal {{ $counts['teal'] }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">Green {{ $counts['green'] }}</span>
                </div>
            </div>

            {{ $this->form }}
        </div>

        <div class="flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-300">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#DC2626"></span> 0–25%</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#D97706"></span> 26–50%</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#0D9488"></span> 51–75%</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#16A34A"></span> 76–100%</span>
        </div>

        @if(count($visibleLearners) === 0)
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-10 text-center text-gray-500 dark:text-gray-400">
                No learners match these filters
                @if(!$cacheGeneratedAt)
                    (or the daily heat map cache has not been built yet).
                @endif
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                @foreach($visibleLearners as $learner)
                    <button
                        type="button"
                        wire:click="openLearner({{ (int) $learner['id'] }})"
                        class="cohort-heat-cell text-left rounded-xl p-3 shadow-sm border border-black/5 hover:shadow-md hover:scale-[1.02] transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        style="background: {{ $learner['display_band_color'] }}; color: #fff;"
                        title="{{ $learner['name'] }} — {{ $learner['display_progress'] }}%"
                    >
                        <div class="text-sm font-semibold leading-tight line-clamp-2 min-h-[2.5rem]">
                            {{ $learner['name'] }}
                        </div>
                        <div class="mt-2 text-2xl font-bold tabular-nums">
                            {{ number_format($learner['display_progress'], 0) }}%
                        </div>
                        <div class="mt-1 text-[11px] uppercase tracking-wide opacity-90">
                            {{ $learner['display_band_label'] }}
                        </div>
                        @if(!empty($learner['state']))
                            <div class="mt-2 text-[11px] opacity-90 truncate">
                                {{ $learner['state'] }}{{ !empty($learner['lga']) ? ', '.$learner['lga'] : '' }}
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
