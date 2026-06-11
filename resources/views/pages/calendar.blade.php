@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Carbon;

    // Bulan aktif dari ?periode=YYYY-MM, default = bulan ini.
    $periodeReq = request('periode', date('Y-m'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodeReq)) {
        $periodeReq = date('Y-m');
    }
    [$year, $month] = array_map('intval', explode('-', $periodeReq));
    $cursor = Carbon::create($year, $month, 1);
    $daysInMonth = (int) $cursor->daysInMonth;
    $prev = $cursor->copy()->subMonth()->format('Y-m');
    $next = $cursor->copy()->addMonth()->format('Y-m');
    $months = voltra_month_names();
    $monthLabel = $months[$month - 1] . ' ' . $year;

    $genset = collect($d['genset'])->where('status', '!=', 'terjual');
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];

    // Index ketersediaan per genset+tanggal (hanya untuk bulan yang ditampilkan).
    $monthPrefix = sprintf('%04d-%02d', $year, $month);
    $avail = [];
    foreach ($d['jadwal_ketersediaan'] as $j) {
        $tgl = (string) $j['tanggal'];
        if (str_starts_with($tgl, $monthPrefix)) {
            $avail[$j['id_genset']][$tgl] = $j['status'];
        }
    }

    $colorFor = [
        'disewa' => '#177f8a',
        'maintenance' => '#d18b1f',
        'tidak_tersedia' => '#e56362',
        'tersedia' => '#e6e9ef',
    ];
    $days = range(1, $daysInMonth);
@endphp

<x-section-header title="Kalender Ketersediaan"
    subtitle="Pantau jadwal unit per hari: tersedia, disewa, sedang diservis, atau tidak tersedia">
    <x-slot:actions>
        <a href="{{ route('rental') }}" class="btn btn-primary"><x-icon name="plus" :size="14" /> Booking</a>
    </x-slot:actions>
</x-section-header>

<div class="flex items-center justify-between py-3">
    <div class="flex items-center gap-2">
        <a href="{{ route('calendar', ['periode' => $prev]) }}" class="btn btn-ghost" title="Bulan sebelumnya">‹</a>
        <div class="font-semibold text-ink-800 text-[14px] min-w-[140px] text-center">{{ $monthLabel }}</div>
        <a href="{{ route('calendar', ['periode' => $next]) }}" class="btn btn-ghost" title="Bulan berikutnya">›</a>
        @if ($periodeReq !== date('Y-m'))
            <a href="{{ route('calendar') }}" class="btn btn-ghost text-[11.5px] ml-1">Hari Ini</a>
        @endif
    </div>
    <div class="flex items-center gap-3 text-[12px]">
        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded" style="background:#177f8a"></div>Disewa</div>
        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded" style="background:#d18b1f"></div>Maintenance</div>
        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded" style="background:#e56362"></div>Tidak Tersedia</div>
        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded border border-ink-300" style="background:#e6e9ef"></div>Tersedia</div>
    </div>
</div>

<div class="card overflow-x-auto">
    <table class="min-w-full text-[11px]">
        <thead>
            <tr>
                <th class="sticky left-0 bg-ink-50 px-3 py-2 text-left font-semibold text-[10.5px] uppercase tracking-wider text-ink-500 border-b border-ink-200 w-[260px]">Unit</th>
                @foreach ($days as $day)
                    <th class="bg-ink-50 px-0 py-2 text-center font-medium text-ink-500 border-b border-ink-200 border-l border-ink-100" style="width:26px">{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if ($genset->isEmpty())
                <tr>
                    <td colspan="{{ count($days) + 1 }}" class="px-4 py-6 text-center text-ink-500 italic">
                        Belum ada unit genset. Tambahkan unit di menu Aset terlebih dahulu.
                    </td>
                </tr>
            @else
                @foreach ($genset as $g)
                    <tr>
                        <td class="sticky left-0 bg-white px-3 py-2 border-b border-ink-100">
                            <div class="font-medium text-[12.5px]">{{ $merekById[$g['id_merek']]['nama_merek'] ?? '—' }} {{ $kategoriById[$g['id_kategori']]['kapasitas'] ?? '' }}</div>
                            <div class="mono text-[10.5px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                        </td>
                        @foreach ($days as $day)
                            @php
                                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $st = $avail[$g['id_genset']][$date] ?? 'tersedia';
                            @endphp
                            <td class="border-b border-ink-100 border-l border-ink-100 p-0.5">
                                <div class="h-6 rounded-sm" style="background:{{ $colorFor[$st] }}" title="{{ $g['nomor_seri'] }} · {{ $day }} {{ $months[$month - 1] }} {{ $year }} · {{ lbl($st) }}"></div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
@endsection
