@props([
    'show' => 'false',
    'close' => 'open = null',
    'title' => '',
    'subtitle' => null,
    'width' => 560,
])
{{-- Side drawer. `show` is an Alpine expression; `close` runs on dismiss. --}}
<div x-show="{{ $show }}" x-cloak class="fixed inset-0 z-50 flex" style="display:none">
    <div class="flex-1 bg-ink-900/30" @click="{{ $close }}"></div>
    <div class="bg-white shadow-pop flex flex-col fadein" style="width:{{ $width }}px">
        <div class="px-5 py-4 border-b border-ink-200 flex items-start justify-between">
            <div>
                <div class="text-[16px] font-semibold text-ink-800">{{ $title }}</div>
                @if ($subtitle)
                    <div class="text-[12px] text-ink-500 mt-0.5">{!! $subtitle !!}</div>
                @endif
            </div>
            <button type="button" class="text-ink-400 hover:text-ink-700" @click="{{ $close }}">
                <x-icon name="x" :size="18" />
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-5">{{ $slot }}</div>
        @isset($footer)
            <div class="px-5 py-3 border-t border-ink-200 bg-ink-50 flex items-center justify-end gap-2">{{ $footer }}</div>
        @endisset
    </div>
</div>
