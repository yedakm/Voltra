@extends('layouts.app')

@section('content')
@php
    use App\Support\VoltraData;

    $genset = collect($d['genset']);
    $transaksi = collect($d['transaksi_sewa']);
    $kategori = collect($d['kategori_genset']);
    $months = voltra_month_names();

    $totalAset = $genset->count();
    $diProyek = $genset->where('status', 'di_proyek')->count();
    $utilization = $totalAset ? round($diProyek / $totalAset * 100) : 0;

    $outstandingRows = $transaksi->filter(fn ($s) => $s['status_pembayaran'] !== 'lunas' && $s['status_pesanan'] !== 'dibatalkan');
    $outstandingTotal = $outstandingRows->sum(fn ($s) => VoltraData::sewaOutstanding($s)['sisa']);

    $monthlyDepreciation = collect($d['jadwal_penyusutan'])->sum('beban_penyusutan');

    $revMonth = $transaksi
        ->filter(fn ($s) => $s['tgl_terbit_invoice'] && str_starts_with($s['tgl_terbit_invoice'], '2026-04') && $s['status_pesanan'] !== 'dibatalkan')
        ->sum(fn ($s) => $s['total_tagihan'] + $s['pajak']);

    $lowStock = collect($d['suku_cadang'])->filter(fn ($p) => $p['stok_tersedia'] < 10)->values();
    $periodeAktif = collect($d['periode_akuntansi'])->firstWhere('status', 'aktif');
    $periodeLabel = $periodeAktif
        ? ($months[$periodeAktif['bulan'] - 1] . ' ' . $periodeAktif['tahun'])
        : 'Belum ada periode aktif';
    $tenantNama = $d['TENANT']['nama_perusahaan'] ?? '—';

    // Sparkline
    $spark = [12, 18, 14, 22, 28, 19, 32];
    $sw = 260; $sh = 70;
    $mx = max($spark); $mn = min($spark); $rng = ($mx - $mn) ?: 1;
    $pts = [];
    foreach ($spark as $i => $v) {
        $x = ($i / (count($spark) - 1)) * $sw;
        $y = $sh - (($v - $mn) / $rng) * $sh;
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }
    $sparkPts = implode(' ', $pts);

    // ===== Diagram Pendapatan vs Beban per bulan (dari jurnal yang diposting) =====
    $akunByKode = $d['akunByKode'];
    $detByJurnal = collect($d['detail_jurnal'])->groupBy('id_jurnal');
    $byMonth = [];
    // Sumbu bulan diawali dari periode akuntansi yang ada (agar tampil sebagai tren bulanan).
    foreach ($d['periode_akuntansi'] as $p) {
        $byMonth[sprintf('%04d-%02d', $p['tahun'], $p['bulan'])] = ['pendapatan' => 0, 'beban' => 0];
    }
    foreach ($d['jurnal_akuntansi'] as $jr) {
        $ym = substr((string) $jr['tanggal'], 0, 7);
        $byMonth[$ym] ??= ['pendapatan' => 0, 'beban' => 0];
        foreach ($detByJurnal[$jr['id_jurnal']] ?? [] as $l) {
            $kat = $akunByKode[$l['kode_akun']]['kategori_akun'] ?? null;
            if ($kat === 'pendapatan') {
                $byMonth[$ym]['pendapatan'] += (float) $l['kredit'] - (float) $l['debit'];
            } elseif ($kat === 'beban') {
                $byMonth[$ym]['beban'] += (float) $l['debit'] - (float) $l['kredit'];
            }
        }
    }
    ksort($byMonth);
    $maxBar = 0;
    foreach ($byMonth as $v) {
        $maxBar = max($maxBar, $v['pendapatan'], $v['beban']);
    }
    $mShort = voltra_month_short();
    $monthlyChart = [];
    foreach ($byMonth as $ym => $v) {
        [$yy, $mm] = explode('-', $ym);
        $monthlyChart[] = [
            'label' => $mShort[(int) $mm - 1] . " '" . substr($yy, -2),
            'pendapatan' => $v['pendapatan'],
            'beban' => $v['beban'],
            'pendapatanPct' => ($maxBar > 0 && $v['pendapatan'] > 0) ? max(3, round($v['pendapatan'] / $maxBar * 100)) : 0,
            'bebanPct' => ($maxBar > 0 && $v['beban'] > 0) ? max(3, round($v['beban'] / $maxBar * 100)) : 0,
        ];
    }
    $totalPendapatan = array_sum(array_column($monthlyChart, 'pendapatan'));
    $totalBeban = array_sum(array_column($monthlyChart, 'beban'));
@endphp

<x-section-header title="Dashboard Operasional"
    subtitle="Periode aktif: {{ $periodeLabel }} · {{ $tenantNama }}">
    <x-slot:actions>
        <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
        <a href="{{ route('rental') }}" class="btn btn-primary"><x-icon name="plus" :size="14" /> Sewa Baru</a>
    </x-slot:actions>
</x-section-header>

<div class="grid grid-cols-4 gap-3 mb-5">
    <x-stat-card label="Pendapatan Bulan Ini" :value="fmtIDR($revMonth)" sub="dari invoice yang terbit" tone="brand" icon="invoice" />
    <x-stat-card label="Utilisasi Aset" value="{{ $utilization }}%" sub="{{ $diProyek }} dari {{ $totalAset }} unit sedang di proyek" tone="default" icon="asset" />
    <x-stat-card label="Piutang Terbuka" :value="fmtIDR($outstandingTotal)" sub="{{ $outstandingRows->count() }} invoice belum lunas" tone="warn" icon="doc" />
    <x-stat-card label="Beban Penyusutan Bulan Ini" :value="fmtIDR($monthlyDepreciation)" sub="{{ count($d['jadwal_penyusutan']) }} unit aset" tone="default" icon="ledger" />
</div>

{{-- ===== Diagram Pendapatan vs Beban per Bulan ===== --}}
<div class="card p-5 mb-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-[14px] font-semibold text-ink-800">Pendapatan vs Beban per Bulan</div>
            <div class="text-[12px] text-ink-500">Direkap dari jurnal akuntansi yang sudah diposting</div>
        </div>
        <div class="flex items-center gap-4 text-[11.5px] text-ink-600">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#177f8a"></span> Pendapatan</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#e56362"></span> Beban</span>
        </div>
    </div>

    @if (count($monthlyChart) === 0)
        <div class="text-center text-ink-400 text-[12.5px] py-10">Belum ada jurnal untuk ditampilkan.</div>
    @else
        <div class="flex items-end gap-2" style="height:190px">
            @foreach ($monthlyChart as $row)
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <div class="flex items-end justify-center gap-1.5 w-full" style="height:155px">
                        <div class="rounded-t" style="width:42%;min-width:10px;background:#177f8a;height:{{ $row['pendapatanPct'] }}%"
                             title="Pendapatan {{ $row['label'] }}: {{ fmtIDR($row['pendapatan']) }}"></div>
                        <div class="rounded-t" style="width:42%;min-width:10px;background:#e56362;height:{{ $row['bebanPct'] }}%"
                             title="Beban {{ $row['label'] }}: {{ fmtIDR($row['beban']) }}"></div>
                    </div>
                    <div class="text-[11px] text-ink-500 mt-2 font-medium">{{ $row['label'] }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 pt-3 border-t border-ink-100 grid grid-cols-2 gap-3">
            <div>
                <div class="text-[11px] text-ink-500 uppercase tracking-wider">Total Pendapatan</div>
                <div class="text-[15px] font-semibold text-emerald-700 mono mt-0.5">{{ fmtIDR($totalPendapatan) }}</div>
            </div>
            <div>
                <div class="text-[11px] text-ink-500 uppercase tracking-wider">Total Beban</div>
                <div class="text-[15px] font-semibold text-red-700 mono mt-0.5">{{ fmtIDR($totalBeban) }}</div>
            </div>
        </div>
    @endif
</div>

<div class="grid grid-cols-3 gap-5 mb-5">
    <div class="card p-5 col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="text-[14px] font-semibold text-ink-800">Status Aset per Kategori</div>
                <div class="text-[12px] text-ink-500">Distribusi field <code class="mono text-[11px] bg-ink-50 px-1 rounded">genset.status</code></div>
            </div>
            <a href="{{ route('assets') }}" class="btn btn-ghost text-[12px]">Lihat semua <x-icon name="chevronR" :size="12" /></a>
        </div>
        <div class="space-y-3">
            @foreach ($kategori as $k)
                @php
                    $units = $genset->where('id_kategori', $k['id_kategori']);
                    $rented = $units->where('status', 'di_proyek')->count();
                    $pct = $units->count() ? round($rented / $units->count() * 100) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-[12px] mb-1">
                        <div><span class="font-medium text-ink-800">{{ $k['kapasitas'] }}</span> <span class="text-ink-400">· {{ $units->count() }} unit</span></div>
                        <div class="mono text-ink-600">{{ $rented }}/{{ $units->count() }} ({{ $pct }}%)</div>
                    </div>
                    <div class="h-2 bg-ink-100 rounded-full overflow-hidden">
                        <div class="h-full bg-brand-500" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6 pt-4 border-t border-ink-100 grid grid-cols-5 gap-3 text-center">
            @foreach (['di_proyek', 'di_gudang', 'di_perusahaan', 'rusak', 'terjual'] as $s)
                <div>
                    <div class="text-[10.5px] text-ink-500 uppercase tracking-wider">{{ lbl($s) }}</div>
                    <div class="text-[18px] font-semibold text-ink-700 mt-1">{{ $genset->where('status', $s)->count() }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="text-[14px] font-semibold text-ink-800">Perlu Perhatian</div>
            <span class="pill" style="background:#fef3e8;color:#a6700f">{{ $lowStock->count() + 2 }}</span>
        </div>
        <div class="space-y-3">
            <div class="flex items-start gap-2 text-[12.5px]">
                <div class="mt-0.5 text-red-600"><x-icon name="warn" :size="14" /></div>
                <div class="flex-1">
                    <div class="text-ink-800 font-medium">Periode {{ $periodeLabel }} belum ditutup</div>
                    <div class="text-ink-500 mt-0.5">Validasi &amp; tutup buku via menu Tutup Buku</div>
                </div>
            </div>
            <div class="flex items-start gap-2 text-[12.5px]">
                <div class="mt-0.5 text-amber-600"><x-icon name="warn" :size="14" /></div>
                <div class="flex-1">
                    <div class="text-ink-800 font-medium">1 unit overhaul tertunda</div>
                    <div class="text-ink-500 mt-0.5">Genset CMN-500-0303 berstatus rusak</div>
                </div>
            </div>
            @foreach ($lowStock->take(3) as $p)
                <div class="flex items-start gap-2 text-[12.5px]">
                    <div class="mt-0.5 text-amber-600"><x-icon name="box" :size="14" /></div>
                    <div class="flex-1">
                        <div class="text-ink-800 font-medium">Stok {{ $p['nama_part'] }} tinggal {{ $p['stok_tersedia'] }}</div>
                        <div class="text-ink-500 mt-0.5 mono text-[11px]">{{ $p['kode_sku'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-5">
    <div class="card col-span-2">
        <div class="px-5 py-3 border-b border-ink-100 flex items-center justify-between">
            <div class="text-[14px] font-semibold text-ink-800">Penyewaan Aktif</div>
            <a href="{{ route('rental') }}" class="btn btn-ghost text-[12px]">Lihat semua <x-icon name="chevronR" :size="12" /></a>
        </div>
        <table class="w-full text-[13px]">
            <thead>
                <tr class="text-[11px] text-ink-500 uppercase tracking-wider bg-ink-50/50 border-b border-ink-100">
                    <th class="text-left px-5 py-2 font-semibold">Kontrak</th>
                    <th class="text-left px-3 py-2 font-semibold">Pelanggan</th>
                    <th class="text-left px-3 py-2 font-semibold">Genset</th>
                    <th class="text-left px-3 py-2 font-semibold">Periode</th>
                    <th class="text-right px-5 py-2 font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transaksi->where('status_pesanan', 'deal') as $s)
                    @php $det = $d['detailSewaBySewa'][$s['id_sewa']] ?? []; @endphp
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-5 py-2.5 mono text-[12px]">{{ $s['no_referensi_kontrak'] }}</td>
                        <td class="px-3 py-2.5">{{ $d['pelangganById'][$s['id_pelanggan']]['nama_perusahaan'] }}</td>
                        <td class="px-3 py-2.5">
                            @foreach ($det as $dd)
                                <div class="mono text-[11.5px] text-ink-600">{{ $d['gensetById'][$dd['id_genset']]['nomor_seri'] ?? '' }}</div>
                            @endforeach
                        </td>
                        <td class="px-3 py-2.5 text-ink-600">
                            @if (isset($det[0])){{ fmtDateShort($det[0]['start_date']) }} – {{ fmtDateShort($det[0]['end_date']) }}@endif
                        </td>
                        <td class="px-5 py-2.5 text-right tabular-nums font-medium">{{ fmtIDR($s['total_tagihan'] + $s['pajak']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card p-5">
        <div class="text-[14px] font-semibold text-ink-800 mb-1">Arus Kas 7 Hari</div>
        <div class="text-[12px] text-ink-500 mb-4">Penerimaan vs beban operasional</div>
        <svg width="{{ $sw }}" height="{{ $sh }}" class="overflow-visible">
            <polyline points="{{ $sparkPts }}" fill="none" stroke="#177f8a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <div class="mt-4 grid grid-cols-2 gap-3 pt-3 border-t border-ink-100">
            <div>
                <div class="text-[11px] text-ink-500 uppercase tracking-wider">Kas Masuk</div>
                <div class="text-[15px] font-semibold text-emerald-700 mono mt-0.5">{{ fmtIDR(342812500) }}</div>
            </div>
            <div>
                <div class="text-[11px] text-ink-500 uppercase tracking-wider">Kas Keluar</div>
                <div class="text-[15px] font-semibold text-red-700 mono mt-0.5">{{ fmtIDR(58240000) }}</div>
            </div>
        </div>
        <a href="{{ route('reports') }}" class="btn btn-ghost w-full justify-center mt-4 text-[12px]">
            Buka Laporan Lengkap <x-icon name="chevronR" :size="11" />
        </a>
    </div>
</div>
@endsection
