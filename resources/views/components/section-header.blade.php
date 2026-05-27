@props(['title' => '', 'subtitle' => null])
<div class="flex items-end justify-between mb-4">
    <div>
        <h1 class="text-[20px] font-semibold text-ink-800">{{ $title }}</h1>
        @if ($subtitle)
            <div class="text-[13px] text-ink-500 mt-0.5">{{ $subtitle }}</div>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
