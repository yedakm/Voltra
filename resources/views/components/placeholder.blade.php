@props(['label' => '', 'height' => 160])
<div class="placeholder-stripes rounded border border-ink-200 flex items-center justify-center" style="height:{{ $height }}px">
    <div class="mono text-[11px] text-ink-500 bg-white/80 px-2 py-1 rounded">{{ $label }}</div>
</div>
