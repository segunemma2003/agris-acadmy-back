@php
    /** @var array<string, mixed>|null $learner */
@endphp

@if(!$learner)
    <p class="text-sm text-gray-500">Learner not found in the current snapshot.</p>
@else
    <div class="space-y-5">
        <div class="flex items-start gap-3">
            @if(!empty($learner['avatar']))
                <img src="{{ $learner['avatar'] }}" alt="" class="w-14 h-14 rounded-full object-cover">
            @else
                <div class="w-14 h-14 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg font-semibold text-gray-600 dark:text-gray-200">
                    {{ strtoupper(substr($learner['name'] ?? '?', 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $learner['name'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 break-all">{{ $learner['email'] }}</p>
                @if(!empty($learner['phone']))
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $learner['phone'] }}</p>
                @endif
            </div>
        </div>

        <div
            class="rounded-lg px-4 py-3 text-white"
            style="background: {{ $learner['display_band_color'] ?? '#64748B' }}"
        >
            <p class="text-sm opacity-90">Progress in current filter</p>
            <p class="text-3xl font-bold tabular-nums">{{ number_format($learner['display_progress'] ?? 0, 1) }}%</p>
            <p class="text-xs uppercase tracking-wide mt-1 opacity-90">{{ $learner['display_band_label'] ?? '' }}</p>
        </div>

        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">State</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $learner['state'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">LGA</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $learner['lga'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Gender</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $learner['gender'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Occupation</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $learner['occupation'] ?: '—' }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-gray-500 dark:text-gray-400">Last active</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $learner['last_active_date'] ?: '—' }}</dd>
            </div>
        </dl>

        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Course progress</h3>
            @forelse($learner['enrollments'] ?? [] as $enrollment)
                <div class="mb-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment['course_title'] }}</p>
                        <span class="text-sm tabular-nums font-semibold">{{ number_format($enrollment['progress_percentage'], 0) }}%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 capitalize">{{ $enrollment['status'] }}</p>
                    @if(!empty($enrollment['modules']))
                        <ul class="mt-2 space-y-1">
                            @foreach($enrollment['modules'] as $module)
                                <li class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                                    <span>{{ $module['module_title'] }}</span>
                                    <span class="tabular-nums">
                                        {{ $module['completed_topics'] }}/{{ $module['total_topics'] }}
                                        ({{ number_format($module['progress_percentage'], 0) }}%)
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No enrollments in this snapshot.</p>
            @endforelse
        </div>
    </div>
@endif
