@extends('layouts.app')

@section('content')
@php
    use App\Support\VoltraData;

    $detailJurnal = collect($d['detail_jurnal']);
    $jurnalById = $d['jurnalById'];
    $akunByKode = $d['akunByKode'];
    $tenant = $d['TENANT'];

    $sumAkun = fn ($prefix, $kind) => $detailJurnal
        ->filter(fn ($x) => str_starts_with($x['kode_akun'], $prefix))
        ->sum($kind);

    // --- Laba Rugi ---
    $pendapatanSewa = $sumAkun('4-1001', 'kredit') - $sumAkun('4-1001', 'debit');
    $pendapatanOpr = $sumAkun('4-1002', 'kredit') - $sumAkun('4-1002', 'debit');
    $totalPendapatan = $pendapatanSewa + $pendapatanOpr;
    $bebanPenyusutan = $sumAkun('5-1001', 'debit') - $sumAkun('5-1001', 'kredit');
    $bebanServis = $sumAkun('5-2001', 'debit') - $sumAkun('5-2001', 'kredit');
    $bebanBbm = $sumAkun('5-3001', 'debit') - $sumAkun('5-3001', 'kredit');
    $totalBeban = $bebanPenyusutan + $bebanServis + $bebanBbm;
    $labaBersih = $totalPendapatan - $totalBeban;

    // --- Neraca ---
    $kas = $sumAkun('1-1001', 'debit') - $sumAkun('1-1001', 'kredit') + 1500000000;
    $piutang = $sumAkun('1-1101', 'debit') - $sumAkun('1-1101', 'kredit');
    $persediaan = collect($d['suku_cadang'])->sum(fn ($p) => $p['stok_tersedia'] * $p['harga_satuan']);
    $aktifGenset = collect($d['genset'])->where('status', '!=', 'terjual');
    $asetTetap = $aktifGenset->sum('harga_perolehan');
    $akumulasi = $aktifGenset->sum(fn ($g) => VoltraData::depresiasiInfo($g)['accumulated']);
    $asetTetapBersih = $asetTetap - $akumulasi;
    $totalAset = $kas + $piutang + $persediaan + $asetTetapBersih;
    $ppn = $sumAkun('2-2001', 'kredit') - $sumAkun('2-2001', 'debit');
    $totalKewajiban = $ppn;
    $modal = 5000000000;
    $labaBerjalan = $totalAset - $totalKewajiban - $modal;

    // --- Arus Kas ---
    $kasMasuk = collect($d['pembayaran'])->sum('nominal_bayar');
    $kasKeluar = collect($d['jurnal_akuntansi'])
        ->whereIn('jenis_jurnal', ['beban_operasional', 'pemeliharaan'])
        ->sum('total_debit');

    // --- Buku Besar (precompute per account) ---
    $realAccounts = collect($d['akun_perkiraan'])->filter(fn ($a) => $a['sub_kategori'] !== 'header')->values();
    $ledgers = [];
    foreach ($realAccounts as $a) {
        $saldo = 0;
        $rows = [];
        foreach ($detailJurnal->where('kode_akun', $a['kode_akun']) as $dj) {
            $j = $jurnalById[$dj['id_jurnal']];
            $delta = $a['saldo_normal'] === 'debit' ? ($dj['debit'] - $dj['kredit']) : ($dj['kredit'] - $dj['debit']);
            $saldo += $delta;
            $rows[] = [
                'tanggal' => $j['tanggal'], 'no_bukti' => $j['no_bukti'], 'keterangan' => $j['keterangan'],
                'debit' => $dj['debit'], 'kredit' => $dj['kredit'], 'saldo' => $saldo,
            ];
        }
        $ledgers[$a['kode_akun']] = ['akun' => $a, 'rows' => $rows];
    }
@endphp

<div x-data="{ tab: 'laba_rugi', akun: '1-1101' }">
    <x-section-header title="Laporan Keuangan"
        subtitle="Laporan otomatis dari seluruh jurnal yang sudah tercatat">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> PDF</button>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Excel</button>
        </x-slot:actions>
    </x-section-header>

    <x-tab-bar :tabs="[
        ['id' => 'laba_rugi', 'label' => 'Laba / Rugi'],
        ['id' => 'neraca', 'label' => 'Neraca'],
        ['id' => 'arus_kas', 'label' => 'Arus Kas'],
        ['id' => 'buku_besar', 'label' => 'Buku Besar'],
    ]" />

    <div class="mt-4">
        {{-- ===== Laba Rugi ===== --}}
        <div x-show="tab === 'laba_rugi'">
            @php $mxLR = max($totalPendapatan, $totalBeban, 1); @endphp
            <div class="card p-5 mb-4 max-w-3xl">
                <div class="text-[13px] font-semibold text-ink-800 mb-4">Grafik Laba / Rugi</div>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-[12px] mb-1"><span class="text-ink-600">Total Pendapatan</span><span class="mono font-medium text-emerald-700">{{ fmtIDR($totalPendapatan) }}</span></div>
                        <div class="h-3.5 bg-ink-100 rounded-full overflow-hidden"><div class="h-full rounded-full" style="background:#1f6a34;width:{{ round(max(0, $totalPendapatan) / $mxLR * 100) }}%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[12px] mb-1"><span class="text-ink-600">Total Beban</span><span class="mono font-medium text-red-700">{{ fmtIDR($totalBeban) }}</span></div>
                        <div class="h-3.5 bg-ink-100 rounded-full overflow-hidden"><div class="h-full rounded-full" style="background:#b42318;width:{{ round(max(0, $totalBeban) / $mxLR * 100) }}%"></div></div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-ink-100 flex items-center justify-between">
                    <span class="text-[13px] font-semibold">Laba Bersih</span>
                    <span class="mono font-bold text-[15px] {{ $labaBersih >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ fmtIDR($labaBersih) }}</span>
                </div>
            </div>
            <div class="card p-6 max-w-3xl">
                <div class="text-center mb-6">
                    <div class="text-[16px] font-semibold">{{ $tenant['nama_perusahaan'] }}</div>
                    <div class="text-[14px] mt-1">LAPORAN LABA RUGI</div>
                    <div class="text-[12px] text-ink-500">Periode 1 – 30 April 2026</div>
                </div>
                <table class="w-full text-[13px]">
                    <tbody>
                        <tr><td colspan="2" class="font-semibold text-ink-800 pt-3 pb-2 border-b border-ink-200">PENDAPATAN</td></tr>
                        <tr><td class="py-1 pl-4">Pendapatan Sewa Genset</td><td class="text-right mono">{{ fmtIDR($pendapatanSewa) }}</td></tr>
                        <tr><td class="py-1 pl-4">Pendapatan Operator &amp; BBM</td><td class="text-right mono">{{ fmtIDR($pendapatanOpr) }}</td></tr>
                        <tr class="border-t border-ink-200"><td class="py-2 font-medium">Total Pendapatan</td><td class="text-right mono font-semibold">{{ fmtIDR($totalPendapatan) }}</td></tr>
                        <tr><td colspan="2" class="font-semibold text-ink-800 pt-4 pb-2 border-b border-ink-200">BEBAN OPERASIONAL</td></tr>
                        <tr><td class="py-1 pl-4">Beban Penyusutan</td><td class="text-right mono">{{ fmtIDR($bebanPenyusutan) }}</td></tr>
                        <tr><td class="py-1 pl-4">Beban Servis &amp; Pemeliharaan</td><td class="text-right mono">{{ fmtIDR($bebanServis) }}</td></tr>
                        <tr><td class="py-1 pl-4">Beban BBM &amp; Operasional</td><td class="text-right mono">{{ fmtIDR($bebanBbm) }}</td></tr>
                        <tr class="border-t border-ink-200"><td class="py-2 font-medium">Total Beban</td><td class="text-right mono font-semibold text-red-700">({{ fmtIDR($totalBeban) }})</td></tr>
                        <tr class="border-t-2 border-ink-700"><td class="py-3 font-bold text-[14px]">LABA BERSIH</td><td class="text-right mono font-bold text-[14px] text-emerald-700">{{ fmtIDR($labaBersih) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== Neraca ===== --}}
        <div x-show="tab === 'neraca'" x-cloak>
            @php $totN = max($totalAset, 1); @endphp
            <div class="card p-5 mb-4 max-w-4xl">
                <div class="text-[13px] font-semibold text-ink-800 mb-4">Grafik Komposisi Neraca</div>
                <div class="space-y-4">
                    <div>
                        <div class="text-[12px] text-ink-600 mb-1">Aset: <span class="mono font-medium">{{ fmtIDR($totalAset) }}</span></div>
                        <div class="flex h-5 rounded-md overflow-hidden bg-ink-100">
                            <div style="background:#177f8a;width:{{ round(max(0, $kas) / $totN * 100, 1) }}%" title="Kas &amp; Bank: {{ fmtIDR($kas) }}"></div>
                            <div style="background:#1f9aa6;width:{{ round(max(0, $piutang) / $totN * 100, 1) }}%" title="Piutang: {{ fmtIDR($piutang) }}"></div>
                            <div style="background:#5bb8c2;width:{{ round(max(0, $persediaan) / $totN * 100, 1) }}%" title="Persediaan: {{ fmtIDR($persediaan) }}"></div>
                            <div style="background:#9ad4da;width:{{ round(max(0, $asetTetapBersih) / $totN * 100, 1) }}%" title="Aset Tetap Bersih: {{ fmtIDR($asetTetapBersih) }}"></div>
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[10.5px] text-ink-500">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#177f8a"></span>Kas &amp; Bank</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#1f9aa6"></span>Piutang</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#5bb8c2"></span>Persediaan</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#9ad4da"></span>Aset Tetap Bersih</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[12px] text-ink-600 mb-1">Kewajiban + Ekuitas: <span class="mono font-medium">{{ fmtIDR($totalAset) }}</span></div>
                        <div class="flex h-5 rounded-md overflow-hidden bg-ink-100">
                            <div style="background:#a6700f;width:{{ round(max(0, $totalKewajiban) / $totN * 100, 1) }}%" title="Kewajiban: {{ fmtIDR($totalKewajiban) }}"></div>
                            <div style="background:#5b2ea8;width:{{ round(max(0, $modal) / $totN * 100, 1) }}%" title="Modal: {{ fmtIDR($modal) }}"></div>
                            <div style="background:#8a63c9;width:{{ round(max(0, $labaBerjalan) / $totN * 100, 1) }}%" title="Laba Berjalan: {{ fmtIDR($labaBerjalan) }}"></div>
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[10.5px] text-ink-500">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#a6700f"></span>Kewajiban</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#5b2ea8"></span>Modal</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#8a63c9"></span>Laba Berjalan</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-center text-[11px] text-emerald-700">✓ Total Aset = Total Kewajiban + Ekuitas (seimbang)</div>
            </div>
            <div class="card p-6 max-w-4xl">
                <div class="text-center mb-6">
                    <div class="text-[16px] font-semibold">{{ $tenant['nama_perusahaan'] }}</div>
                    <div class="text-[14px] mt-1">NERACA</div>
                    <div class="text-[12px] text-ink-500">Per 30 April 2026</div>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <table class="w-full text-[12.5px]">
                        <tbody>
                            <tr><td class="font-semibold pb-2 border-b border-ink-200">ASET</td><td></td></tr>
                            <tr><td class="pt-3 pl-2 italic text-ink-600">Aset Lancar</td><td></td></tr>
                            <tr><td class="py-1 pl-4">Kas &amp; Bank</td><td class="text-right mono">{{ fmtIDR($kas) }}</td></tr>
                            <tr><td class="py-1 pl-4">Piutang Usaha</td><td class="text-right mono">{{ fmtIDR($piutang) }}</td></tr>
                            <tr><td class="py-1 pl-4">Persediaan Suku Cadang</td><td class="text-right mono">{{ fmtIDR($persediaan) }}</td></tr>
                            <tr><td class="pt-3 pl-2 italic text-ink-600">Aset Tetap</td><td></td></tr>
                            <tr><td class="py-1 pl-4">Aset Tetap - Genset</td><td class="text-right mono">{{ fmtIDR($asetTetap) }}</td></tr>
                            <tr><td class="py-1 pl-4 text-red-700">(Akumulasi Penyusutan)</td><td class="text-right mono text-red-700">({{ fmtIDR($akumulasi) }})</td></tr>
                            <tr><td class="py-1 pl-4 text-ink-600 italic">Aset Tetap Bersih</td><td class="text-right mono italic">{{ fmtIDR($asetTetapBersih) }}</td></tr>
                            <tr class="border-t-2 border-ink-700"><td class="py-2 font-bold">TOTAL ASET</td><td class="text-right mono font-bold">{{ fmtIDR($totalAset) }}</td></tr>
                        </tbody>
                    </table>
                    <table class="w-full text-[12.5px]">
                        <tbody>
                            <tr><td class="font-semibold pb-2 border-b border-ink-200">KEWAJIBAN &amp; EKUITAS</td><td></td></tr>
                            <tr><td class="pt-3 pl-2 italic text-ink-600">Kewajiban</td><td></td></tr>
                            <tr><td class="py-1 pl-4">PPN Keluaran</td><td class="text-right mono">{{ fmtIDR($ppn) }}</td></tr>
                            <tr><td class="py-1 pl-4 italic">Total Kewajiban</td><td class="text-right mono italic">{{ fmtIDR($totalKewajiban) }}</td></tr>
                            <tr><td class="pt-4 pl-2 italic text-ink-600">Ekuitas</td><td></td></tr>
                            <tr><td class="py-1 pl-4">Modal Disetor</td><td class="text-right mono">{{ fmtIDR($modal) }}</td></tr>
                            <tr><td class="py-1 pl-4">Laba Berjalan</td><td class="text-right mono">{{ fmtIDR($labaBerjalan) }}</td></tr>
                            <tr class="border-t-2 border-ink-700"><td class="py-2 font-bold">TOTAL KEWAJIBAN + EKUITAS</td><td class="text-right mono font-bold">{{ fmtIDR($totalAset) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== Arus Kas ===== --}}
        <div x-show="tab === 'arus_kas'" x-cloak>
            @php $mxAK = max($kasMasuk, $kasKeluar, 1); @endphp
            <div class="card p-5 mb-4 max-w-3xl">
                <div class="text-[13px] font-semibold text-ink-800 mb-4">Grafik Arus Kas</div>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-[12px] mb-1"><span class="text-ink-600">Kas Masuk</span><span class="mono font-medium text-emerald-700">{{ fmtIDR($kasMasuk) }}</span></div>
                        <div class="h-3.5 bg-ink-100 rounded-full overflow-hidden"><div class="h-full rounded-full" style="background:#1f6a34;width:{{ round(max(0, $kasMasuk) / $mxAK * 100) }}%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[12px] mb-1"><span class="text-ink-600">Kas Keluar</span><span class="mono font-medium text-red-700">{{ fmtIDR($kasKeluar) }}</span></div>
                        <div class="h-3.5 bg-ink-100 rounded-full overflow-hidden"><div class="h-full rounded-full" style="background:#b42318;width:{{ round(max(0, $kasKeluar) / $mxAK * 100) }}%"></div></div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-ink-100 flex items-center justify-between">
                    <span class="text-[13px] font-semibold">Kas Bersih</span>
                    <span class="mono font-bold text-[15px] {{ ($kasMasuk - $kasKeluar) >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ fmtIDR($kasMasuk - $kasKeluar) }}</span>
                </div>
            </div>
            <div class="card p-6 max-w-3xl">
                <div class="text-center mb-6">
                    <div class="text-[16px] font-semibold">{{ $tenant['nama_perusahaan'] }}</div>
                    <div class="text-[14px] mt-1">LAPORAN ARUS KAS</div>
                    <div class="text-[12px] text-ink-500">Periode 1 – 30 April 2026 · Metode Langsung</div>
                </div>
                <table class="w-full text-[13px]">
                    <tbody>
                        <tr><td colspan="2" class="font-semibold pt-3 pb-2 border-b border-ink-200">ARUS KAS DARI AKTIVITAS OPERASI</td></tr>
                        <tr><td class="py-1 pl-4">Penerimaan dari Pelanggan</td><td class="text-right mono">{{ fmtIDR($kasMasuk) }}</td></tr>
                        <tr><td class="py-1 pl-4 text-red-700">(Pengeluaran Operasional)</td><td class="text-right mono text-red-700">({{ fmtIDR($kasKeluar) }})</td></tr>
                        <tr class="border-t border-ink-200"><td class="py-2 font-medium">Kas Bersih dari Operasi</td><td class="text-right mono font-semibold">{{ fmtIDR($kasMasuk - $kasKeluar) }}</td></tr>
                        <tr><td colspan="2" class="font-semibold pt-4 pb-2 border-b border-ink-200">ARUS KAS DARI AKTIVITAS INVESTASI</td></tr>
                        <tr><td class="py-1 pl-4">Pembelian Aset Tetap</td><td class="text-right mono">{{ fmtIDR(0) }}</td></tr>
                        <tr><td class="py-1 pl-4">Penjualan Aset Tetap</td><td class="text-right mono">{{ fmtIDR(0) }}</td></tr>
                        <tr class="border-t-2 border-ink-700"><td class="py-3 font-bold text-[14px]">KENAIKAN KAS BERSIH</td><td class="text-right mono font-bold text-[14px] text-emerald-700">{{ fmtIDR($kasMasuk - $kasKeluar) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== Buku Besar ===== --}}
        <div x-show="tab === 'buku_besar'" x-cloak>
            <div class="card">
                <div class="p-4 border-b border-ink-100 flex items-center gap-3">
                    <div class="text-[13px] font-medium">Akun:</div>
                    <select class="inp max-w-md" x-model="akun">
                        @foreach ($realAccounts as $a)
                            <option value="{{ $a['kode_akun'] }}">{{ $a['kode_akun'] }} · {{ $a['nama_akun'] }}</option>
                        @endforeach
                    </select>
                    <div class="ml-auto text-[12px] text-ink-500">Saldo normal:
                        <span class="font-medium capitalize"
                              x-text="({{ Illuminate\Support\Js::from($realAccounts->mapWithKeys(fn ($a) => [$a['kode_akun'] => $a['saldo_normal']])) }})[akun]"></span>
                    </div>
                </div>
                @foreach ($ledgers as $kode => $ledger)
                    <table class="w-full text-[12.5px]" x-show="akun === '{{ $kode }}'" x-cloak>
                        <thead class="bg-ink-50 text-[10.5px] uppercase text-ink-500 tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Tanggal</th>
                                <th class="px-3 py-2 text-left font-semibold">No. Bukti</th>
                                <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                                <th class="px-3 py-2 text-right font-semibold">Debit</th>
                                <th class="px-3 py-2 text-right font-semibold">Kredit</th>
                                <th class="px-3 py-2 text-right font-semibold">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (count($ledger['rows']) === 0)
                                <tr><td colspan="6" class="text-center text-ink-400 py-8">Belum ada transaksi pada akun ini</td></tr>
                            @endif
                            @foreach ($ledger['rows'] as $r)
                                <tr class="border-t border-ink-100">
                                    <td class="px-3 py-2">{{ fmtDate($r['tanggal']) }}</td>
                                    <td class="px-3 py-2 mono text-[11.5px]">{{ $r['no_bukti'] }}</td>
                                    <td class="px-3 py-2 text-ink-600">{{ $r['keterangan'] }}</td>
                                    <td class="px-3 py-2 text-right mono">@if ($r['debit'] > 0){{ fmtIDR($r['debit']) }}@else<span class="text-ink-300">—</span>@endif</td>
                                    <td class="px-3 py-2 text-right mono">@if ($r['kredit'] > 0){{ fmtIDR($r['kredit']) }}@else<span class="text-ink-300">—</span>@endif</td>
                                    <td class="px-3 py-2 text-right mono font-medium">{{ fmtIDR($r['saldo']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
