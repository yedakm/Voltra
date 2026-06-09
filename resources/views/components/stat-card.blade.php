@props(['label' => '', 'value' => '', 'sub' => null, 'tone' => 'default', 'icon' => null])
@php
    $toneColors = [
        'default' => '#1c2028',
        'brand'   => '#0b4e56',
        'warn'    => '#a6700f',
        'danger'  => '#b42318',
        'ok'      => '#1f6a34',
    ];
@endphp
<div class="card p-4 flex-1">
    <div class="flex items-start justify-between">
        <div class="text-[11px] uppercase tracking-wider text-ink-500 font-semibold">{{ $label }}</div>
        @if ($icon)
            <div class="text-ink-400"><x-icon :name="$icon" :size="16" /></div>
        @endif
    </div>
    <div class="mt-2 text-[22px] font-semibold tabular-nums" style="color:{{ $toneColors[$tone] ?? $toneColors['default'] }}">{{ $value }}</div>
    @if ($sub)
        <div class="text-[12px] text-ink-500 mt-1">{{ $sub }}</div>
    @endif
</div>
