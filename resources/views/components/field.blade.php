@props(['label' => '', 'hint' => null])
<div>
    <label class="text-[11px] uppercase tracking-wider text-ink-500 font-semibold">{{ $label }}</label>
    <div class="mt-1 text-[13px] text-ink-800">{{ $slot }}</div>
    @if ($hint)
        <div class="text-[11px] text-ink-400 mt-1">{{ $hint }}</div>
    @endif
</div>
