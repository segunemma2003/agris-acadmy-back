<x-filament-widgets::widget>
    <x-filament::section heading="Learner baseline breakdown" description="Share of learners who answered each question. Updates every 10 minutes.">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem">
            @foreach ($groups as $title => $rows)
                <div>
                    <p style="font-weight:600;margin-bottom:.5rem">{{ $title }}</p>
                    @forelse ($rows as $row)
                        <div style="margin-bottom:.45rem">
                            <div style="display:flex;justify-content:space-between;gap:.5rem;font-size:.8rem">
                                <span>{{ $row['label'] }}</span>
                                <span style="white-space:nowrap;opacity:.75">{{ number_format($row['count']) }} · {{ $row['pct'] }}%</span>
                            </div>
                            <div style="height:.4rem;border-radius:9999px;background:rgba(127,127,127,.18);overflow:hidden">
                                <div style="height:100%;width:{{ $row['pct'] }}%;background:#1B7F6E;border-radius:9999px"></div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:.8rem;opacity:.6">No answers yet.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
