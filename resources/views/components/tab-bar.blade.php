@props(['tabs' => [], 'model' => 'tab'])
{{-- Renders inside an existing Alpine x-data scope that owns `$model`. --}}
<div class="border-b border-ink-200 flex items-center gap-6 px-1">
    @foreach ($tabs as $t)
        <div class="tab" :class="{ 'active': {{ $model }} === '{{ $t['id'] }}' }"
             @click="{{ $model }} = '{{ $t['id'] }}'">
            {{ $t['label'] }}
            @isset($t['count'])
                <span class="ml-1.5 text-[11px] text-ink-400">({{ $t['count'] }})</span>
            @endisset
        </div>
    @endforeach
</div>
