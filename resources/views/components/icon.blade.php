@props(['name' => '', 'size' => 16])
@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'rental'    => '<path d="M3 7h13l5 5v5h-3"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M3 7v11h2"/>',
        'asset'     => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h2M7 14h2"/><circle cx="16" cy="12" r="2"/>',
        'wrench'    => '<path d="M14 6a4 4 0 0 0 5 5l2 2-8 8-2-2a4 4 0 0 0-5-5L3 9l6-6 5 3z"/>',
        'ledger'    => '<path d="M4 4h13a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3V4z"/><path d="M4 4v13a3 3 0 0 0 3 3"/><path d="M9 8h7M9 12h7"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'users'     => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 3-5 6-5s6 2 6 5"/><circle cx="17" cy="9" r="2.5"/><path d="M15 20c0-2.5 2-4 4-4"/>',
        'invoice'   => '<path d="M6 3h9l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M15 3v4h4"/><path d="M9 12h6M9 16h6"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'bell'      => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4l2-2z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'chevron'   => '<path d="M6 9l6 6 6-6"/>',
        'chevronR'  => '<path d="M9 6l6 6-6 6"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'filter'    => '<path d="M4 4h16l-6 8v6l-4 2v-8L4 4z"/>',
        'download'  => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/>',
        'check'     => '<path d="M4 12l5 5L20 6"/>',
        'x'         => '<path d="M6 6l12 12M18 6l-12 12"/>',
        'gps'       => '<circle cx="12" cy="11" r="3"/><path d="M12 2v2M12 18v2M2 11h2M20 11h2"/><circle cx="12" cy="11" r="8"/>',
        'power'     => '<path d="M12 3v8"/><path d="M6.3 6.3a8 8 0 1 0 11.4 0"/>',
        'warn'      => '<path d="M12 3l10 18H2L12 3z"/><path d="M12 10v5M12 18v.5"/>',
        'doc'       => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4"/>',
        'dots'      => '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
        'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'edit'      => '<path d="M4 20h4L20 8l-4-4L4 16v4z"/>',
        'trash'     => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'logout'    => '<path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5"/><path d="M15 8l5 4-5 4M9 12h11"/>',
        'tag'       => '<path d="M3 10V4a1 1 0 0 1 1-1h6l11 11-7 7L3 10z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'box'       => '<path d="M3 7l9-4 9 4v10l-9 4-9-4V7z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
        'diesel'    => '<path d="M4 21V8a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13"/><path d="M4 21h14"/><path d="M16 10h2a2 2 0 0 1 2 2v5a1 1 0 0 1-2 0"/><path d="M6 10h8v5H6z"/>',
    ];
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->merge(['style' => 'flex-shrink:0']) }}>
    {!! $paths[$name] ?? '' !!}
</svg>
