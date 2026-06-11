@extends('layouts.app')

@section('content')
@php
    $periode = collect($d['periode_akuntansi']);
    $jurnal = collect($d['jurnal_akuntansi']);
    $penggunaById = $d['penggunaById'];
    $months = voltra_month_names();
@endphp

<div x-data="{
    closing: null, previewing: null, saving: false,
    tutupBuku(idPeriode, label) {
        if (this.saving) return;
        this.saving = true;
        window.voltraSave('/aksi/period/' + idPeriode + '/close',
            {}, 'Periode ' + label + ' berhasil ditutup. Laporan tersedia di menu Laporan.').catch(() => this.saving = false);
    },
    bukaKembali(idPeriode, label) {
        if (this.saving) return;
        if (!confirm('Buka kembali periode ' + label + '? Penjurnalan di bulan ini akan aktif lagi sampai ditutup ulang.')) return;
        this.saving = true;
        window.voltraSave('/aksi/period/' + idPeriode + '/reopen',
            {}, 'Periode ' + label + ' dibuka kembali. Penjurnalan aktif lagi.').catch(() => this.saving = false);
    },
}">
    <x-section-header title="Tutup Buku Periode"
        subtitle="Setelah periode ditutup, penjurnalan di bulan tersebut akan dikunci.">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor laporan tahunan</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-3 gap-3 mb-5">
        <x-stat-card label="Periode Aktif" :value="$periode->where('status', 'aktif')->count()" sub="masih dapat dijurnal" tone="brand" />
        <x-stat-card label="Periode Ditutup" :value="$periode->where('status', 'ditutup')->count()" sub="terkunci" />
        <x-stat-card label="Tahun Berjalan" value="2026" sub="12 periode bulanan" />
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:160px">Periode</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Rentang</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Total Jurnal</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Penyusutan</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Status</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Tgl. Tutup Buku</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Ditutup Oleh</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:140px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($periode as $r)
                    @php $jc = $jurnal->where('id_periode', $r['id_periode'])->count(); @endphp
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5"><span class="font-medium">{{ $months[$r['bulan'] - 1] }} {{ $r['tahun'] }}</span></td>
                        <td class="px-3 py-2.5"><span class="text-[12px] text-ink-600">{{ fmtDate($r['tgl_mulai']) }} – {{ fmtDate($r['tgl_selesai']) }}</span></td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $jc }}</td>
                        <td class="px-3 py-2.5">
                            <x-status-pill :status="($r['status'] === 'ditutup' || $r['id_periode'] <= 4) ? 'posted' : 'pending'" />
                        </td>
                        <td class="px-3 py-2.5"><x-status-pill :status="$r['status']" /></td>
                        <td class="px-3 py-2.5">{{ $r['tgl_tutup_buku'] ? fmtDate($r['tgl_tutup_buku']) : '—' }}</td>
                        <td class="px-3 py-2.5">{{ $r['ditutup_oleh'] ? ($penggunaById[$r['ditutup_oleh']]['nama'] ?? '') : '—' }}</td>
                        <td class="px-3 py-2.5">
                            @if ($r['status'] === 'aktif')
                                <button class="btn btn-primary text-[11px]" @click="closing = {{ $r['id_periode'] }}">Tutup Buku</button>
                            @else
                                <div class="flex items-center gap-1.5">
                                    <button class="btn btn-ghost text-[11px]" @click="previewing = {{ $r['id_periode'] }}"
                                        title="Lihat catatan periode ini tanpa membukanya">
                                        Pratinjau
                                    </button>
                                    <button class="btn btn-ghost text-[11px] text-amber-600" :disabled="saving"
                                        @click="bukaKembali({{ $r['id_periode'] }}, '{{ $months[$r['bulan'] - 1] }} {{ $r['tahun'] }}')"
                                        title="Buka kembali periode untuk koreksi catatan">
                                        Buka Kembali
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== Closing drawers (active periods) ===== --}}
    @foreach ($periode->where('status', 'aktif') as $r)
        @php
            $journals = $jurnal->where('id_periode', $r['id_periode']);
            $totalDebit = $journals->sum('total_debit');
            $totalKredit = $journals->sum('total_kredit');
            $balanced = $totalDebit === $totalKredit;
            $depPosted = $journals->contains('jenis_jurnal', 'penyusutan');
            $checks = [
                ['ok' => $balanced, 'label' => 'Total debit = kredit (' . fmtIDR($totalDebit) . ' vs ' . fmtIDR($totalKredit) . ')'],
                ['ok' => $depPosted, 'label' => 'Jurnal penyusutan akhir bulan sudah ter-generate'],
                ['ok' => $journals->count() > 0, 'label' => $journals->count() . ' jurnal terverifikasi di periode ini'],
                ['ok' => true, 'label' => 'Tidak ada transaksi operasional yang belum dijurnalkan'],
            ];
            $allOk = collect($checks)->every(fn ($c) => $c['ok']);
        @endphp
        <x-drawer show="closing === {{ $r['id_periode'] }}" close="closing = null" :width="600"
            :title="'Tutup Buku · '.$months[$r['bulan'] - 1].' '.$r['tahun']"
            :subtitle="fmtDate($r['tgl_mulai']).' – '.fmtDate($r['tgl_selesai'])">

            <div class="mb-5">
                <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-3">Validasi Kelengkapan</div>
                <div class="space-y-2">
                    @foreach ($checks as $c)
                        <div class="card p-3 flex items-center gap-3">
                            @if ($c['ok'])
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center"><x-icon name="check" :size="14" /></div>
                            @else
                                <div class="w-6 h-6 rounded-full bg-red-100 text-red-700 flex items-center justify-center"><x-icon name="x" :size="14" /></div>
                            @endif
                            <div class="flex-1 text-[12.5px]">{{ $c['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-5">
                <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Ringkasan Aktivitas</div>
                <div class="card p-4 grid grid-cols-2 gap-3 text-[12.5px]">
                    <div><div class="text-ink-500">Total Jurnal</div><div class="font-semibold">{{ $journals->count() }}</div></div>
                    <div><div class="text-ink-500">Total Debit / Kredit</div><div class="font-semibold mono">{{ fmtIDR($totalDebit) }}</div></div>
                    <div><div class="text-ink-500">Sewa Terbit</div><div class="font-semibold">{{ $journals->where('jenis_jurnal', 'sewa')->count() }}</div></div>
                    <div><div class="text-ink-500">Penyusutan</div><div class="font-semibold">{{ $journals->where('jenis_jurnal', 'penyusutan')->count() }}</div></div>
                </div>
            </div>

            <div class="card p-4 bg-amber-50 border-amber-200">
                <div class="flex items-start gap-2">
                    <div class="text-amber-600 mt-0.5"><x-icon name="warn" :size="16" /></div>
                    <div class="text-[12px] text-amber-900">
                        <div class="font-semibold mb-1">Penting</div>
                        Setelah tutup buku, sistem akan menolak penjurnalan baru di periode ini. Pastikan semua transaksi {{ $months[$r['bulan'] - 1] }} sudah ter-input dengan benar.
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <button class="btn btn-ghost" @click="closing = null">Batal</button>
                <button class="btn btn-primary" @if (! $allOk) disabled @endif :disabled="saving"
                    @click="tutupBuku({{ $r['id_periode'] }}, '{{ $months[$r['bulan'] - 1] }} {{ $r['tahun'] }}')">
                    <x-icon name="check" :size="14" /> {{ $allOk ? 'Konfirmasi Tutup Buku' : 'Perbaiki Validasi' }}
                </button>
            </x-slot:footer>
        </x-drawer>
    @endforeach

    {{-- ===== Pratinjau periode ditutup (hanya lihat, tidak membuka kunci) ===== --}}
    @foreach ($periode->where('status', 'ditutup') as $r)
        @php
            $journals = $jurnal->where('id_periode', $r['id_periode'])->sortBy('tanggal');
            $jenisLabel = ['pembelian_aset' => 'Pembelian Aset', 'sewa' => 'Sewa', 'pembayaran' => 'Pembayaran',
                'pemeliharaan' => 'Pemeliharaan', 'beban_operasional' => 'Beban Operasional', 'penyusutan' => 'Penyusutan',
                'penjualan_aset' => 'Penjualan Aset', 'penyesuaian' => 'Penyesuaian', 'penutup' => 'Penutup',
                'manual' => 'Manual', 'koreksi' => 'Koreksi'];
        @endphp
        <x-drawer show="previewing === {{ $r['id_periode'] }}" close="previewing = null" :width="640"
            :title="'Pratinjau · '.$months[$r['bulan'] - 1].' '.$r['tahun']"
            :subtitle="'Periode terkunci. Catatan di bawah hanya untuk dilihat.'">

            <div class="card p-4 grid grid-cols-2 gap-3 text-[12.5px] mb-5">
                <div><div class="text-ink-500">Total Jurnal</div><div class="font-semibold">{{ $journals->count() }}</div></div>
                <div><div class="text-ink-500">Total Debit / Kredit</div><div class="font-semibold mono">{{ fmtIDR($journals->sum('total_debit')) }}</div></div>
                <div><div class="text-ink-500">Ditutup Pada</div><div class="font-semibold">{{ $r['tgl_tutup_buku'] ? fmtDate($r['tgl_tutup_buku']) : '—' }}</div></div>
                <div><div class="text-ink-500">Ditutup Oleh</div><div class="font-semibold">{{ $r['ditutup_oleh'] ? ($penggunaById[$r['ditutup_oleh']]['nama'] ?? '—') : '—' }}</div></div>
            </div>

            <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Catatan Jurnal Periode Ini</div>
            @if ($journals->isEmpty())
                <div class="card p-4 text-[12.5px] text-ink-500">Tidak ada jurnal pada periode ini.</div>
            @else
                <div class="card overflow-hidden">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[10.5px] uppercase tracking-wider">
                                <th class="px-3 py-2 text-left font-semibold">No. Bukti</th>
                                <th class="px-3 py-2 text-left font-semibold">Tanggal</th>
                                <th class="px-3 py-2 text-left font-semibold">Jenis</th>
                                <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                                <th class="px-3 py-2 text-right font-semibold">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($journals as $j)
                                <tr class="border-b border-ink-100">
                                    <td class="px-3 py-2 mono">{{ $j['no_bukti'] }}</td>
                                    <td class="px-3 py-2">{{ fmtDate($j['tanggal']) }}</td>
                                    <td class="px-3 py-2">{{ $jenisLabel[$j['jenis_jurnal']] ?? $j['jenis_jurnal'] }}</td>
                                    <td class="px-3 py-2 text-ink-600">{{ \Illuminate\Support\Str::limit($j['keterangan'], 40) }}</td>
                                    <td class="px-3 py-2 text-right mono">{{ fmtIDR($j['total_debit']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="card p-3 bg-ink-50 mt-4 text-[12px] text-ink-600">
                Ingin mengubah catatan di bulan ini? Tutup pratinjau lalu klik tombol <span class="font-semibold">Buka Kembali</span>.
            </div>

            <x-slot:footer>
                <a href="{{ route('reports') }}" class="btn btn-ghost">Lihat Laporan</a>
                <button class="btn btn-primary" @click="previewing = null">Tutup</button>
            </x-slot:footer>
        </x-drawer>
    @endforeach
</div>
@endsection
