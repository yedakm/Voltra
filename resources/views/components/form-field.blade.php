@props(['label' => '', 'hint' => null, 'required' => false])
<div>
    <label class="text-[12px] font-medium text-ink-700 block mb-1">
        {{ $label }}@if ($required)<span class="text-red-600 ml-0.5">*</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <div class="text-[11px] text-ink-400 mt-1">{{ $hint }}</div>
    @endif
</div>
