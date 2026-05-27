@props(['searchModel' => null, 'placeholder' => 'Cari...'])
<div class="flex items-center justify-between gap-3 py-3">
    <div class="flex items-center gap-2 flex-1">
        <div class="relative max-w-xs w-full">
            <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400"><x-icon name="search" :size="14" /></div>
            <input class="inp pl-8" placeholder="{{ $placeholder }}"
                   @if ($searchModel) x-model="{{ $searchModel }}" @endif />
        </div>
        @isset($filters){{ $filters }}@endisset
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
