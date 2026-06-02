@props([
    'title',
    'value',
    'tone' => 'blue',
])

@php
    $tones = [
        'blue' => [
            'dot' => 'bg-[#ff5a00]',
            'badge' => 'border-[#ff5a00]/20 bg-[#ff5a00]/12 text-[#ffb07a]',
        ],
        'amber' => [
            'dot' => 'bg-amber-400',
            'badge' => 'border-amber-500/20 bg-amber-500/12 text-amber-200',
        ],
        'emerald' => [
            'dot' => 'bg-emerald-400',
            'badge' => 'border-emerald-500/20 bg-emerald-500/12 text-emerald-200',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'min-w-[176px] shrink-0 flex-1 rounded-2xl border border-[#3a3a40] bg-[#232326] px-4 py-3 shadow-sm transition hover:border-[#4a4a50] hover:bg-[#262629]']) }}>
    <div class="flex h-[25px] items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="h-2 w-2 shrink-0 rounded-full {{ $toneClasses['dot'] }}"></span>
            <p class="min-w-0 truncate whitespace-nowrap text-[10.5px] font-semibold uppercase tracking-[0.22em] text-[#a1a1aa]">
                {{ $title }}
            </p>
        </div>

        <span class="inline-flex h-8 min-w-10 shrink-0 items-center justify-center rounded-xl border px-3 text-sm font-semibold {{ $toneClasses['badge'] }}">
            {{ $value }}
        </span>
    </div>
</div>
