@props(['title' => '', 'sub' => null])
<div class="card p-12 text-center">
    <div class="text-ink-400 text-[14px] font-medium">{{ $title }}</div>
    @if ($sub)
        <div class="text-ink-400 text-[12px] mt-1">{{ $sub }}</div>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
