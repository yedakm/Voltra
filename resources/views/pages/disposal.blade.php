@extends('layouts.app')

@section('content')
@php
    use App\Support\VoltraData;

    $penjualan = collect($d['penjualan_genset']);
    $penggunaById = $d['penggunaById'];
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];

    // eligible gensets + depreciation snapshot for the Alpine calculator
    $eligible = [];
    foreach ($d['genset'] as $g) {
        if ($g['status'] === 'terjual') {
            continue;
        }
        $info = VoltraData::depresiasiInfo($g);
        $eligible[$g['id_genset']] = [
            'label' => $g['nomor_seri'] . ' · ' . $merekById[$g['id_merek']]['nama_merek'] . ' ' . $kategoriById[$g['id_kategori']]['kapasitas'],
            'harga' => $g['harga_perolehan'],
            'accumulated' => $info['accumulated'],
            'book' => $info['bookValue'],
        ];
    }
@endphp

<div x-data="{
    createOpen: false, saving: false,
    form: { id_genset: '', harga_jual: 0, tgl_jual: '2026-04-25', keterangan: '' },
    gensets: @js($eligible),
    fmt: window.fmtIDR,
    get sel() { return this.form.id_genset ? this.gensets[this.form.id_genset] : null; },
    get harga() { return Number(this.form.harga_jual || 0); },
    get gainLoss() { return this.sel ? this.harga - this.sel.book : 0; },
    lepasAset() {
        if (!this.sel || !this.harga) return;
        this.saving = true;
        window.voltraSave('/aksi/asset-disposal', {
            id_genset: this.form.id_genset, harga_jual: this.harga,
            tgl_jual: this.form.tgl_jual, keterangan: this.form.keterangan,
        }, r => 'Aset dilepas · ' + (r.penjualan.gain_loss >= 0 ? 'laba ' : 'rugi ') + this.fmt(Math.abs(r.penjualan.gain_loss)))
        .catch(() => this.saving = false);
    },
}">
    <x-section-header title="Pelepasan Aset"
        subtitle="Penjualan atau pembuangan unit. Laba/rugi dihitung dari selisih harga jual dengan nilai buku.">
        <x-slot:actions>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Lepas Aset</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-3 gap-3 mb-5">
        <x-stat-card label="Total Aset Dilepas" value="{{ $penjualan->count() }} unit" sub="historis" />
        <x-stat-card label="Total Realisasi Jual" :value="fmtIDR($penjualan->sum('harga_jual'))" tone="brand" />
        <x-stat-card label="Total Gain (kumulatif)" :value="fmtIDR($penjualan->sum('gain_loss'))" tone="ok" />
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">ID</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:120px">Tgl. Jual</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Genset</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Harga Jual</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Nilai Buku</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Gain / Loss</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Petugas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penjualan as $r)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5 mono text-[12px]">PJG-{{ str_pad($r['id_penjualan'], 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-3 py-2.5">{{ fmtDate($r['tgl_jual']) }}</td>
                        <td class="px-3 py-2.5">
                            <div class="font-medium">{{ explode(',', $r['keterangan'])[0] ?? '—' }}</div>
                            <div class="mono text-[11px] text-ink-500">id_genset #{{ $r['id_genset'] }}</div>
                        </td>
                        <td class="px-3 py-2.5 text-right mono">{{ fmtIDR($r['harga_jual']) }}</td>
                        <td class="px-3 py-2.5 text-right mono">{{ fmtIDR($r['nilai_buku_saat_jual']) }}</td>
                        <td class="px-3 py-2.5 text-right mono font-semibold {{ $r['gain_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $r['gain_loss'] >= 0 ? '+' : '' }}{{ fmtIDR($r['gain_loss']) }}</td>
                        <td class="px-3 py-2.5">{{ $penggunaById[$r['id_pengguna']]['nama'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== Disposal drawer ===== --}}
    <x-drawer show="createOpen" close="createOpen = false" :width="620"
        title="Pelepasan Aset"
        subtitle="Nilai buku unit dihitung otomatis sesuai akumulasi penyusutan">
        <div class="space-y-4">
            <x-form-field label="Unit Genset" :required="true">
                <select class="inp" x-model="form.id_genset">
                    <option value="">— pilih unit aktif —</option>
                    @foreach ($eligible as $id => $e)
                        <option value="{{ $id }}">{{ $e['label'] }}</option>
                    @endforeach
                </select>
            </x-form-field>

            <template x-if="sel">
                <div class="card p-4 bg-ink-50 border-ink-200">
                    <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Nilai Buku (auto)</div>
                    <div class="grid grid-cols-3 gap-3 text-[12.5px]">
                        <div><div class="text-ink-500">Harga Perolehan</div><div class="font-semibold mono" x-text="fmt(sel.harga)"></div></div>
                        <div><div class="text-ink-500">Akumulasi Penyusutan</div><div class="font-semibold mono text-red-700" x-text="fmt(sel.accumulated)"></div></div>
                        <div><div class="text-ink-500">Nilai Buku Saat Ini</div><div class="font-semibold mono text-brand-700" x-text="fmt(sel.book)"></div></div>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Tgl. Jual" :required="true"><input type="date" class="inp" x-model="form.tgl_jual" /></x-form-field>
                <x-form-field label="Harga Jual" :required="true"><input type="number" class="inp" x-model="form.harga_jual" /></x-form-field>
            </div>

            <x-form-field label="Keterangan"><textarea class="inp" rows="2" x-model="form.keterangan"></textarea></x-form-field>

            <template x-if="sel && harga > 0">
                <div class="space-y-4">
                    <div class="card p-4 border-2"
                         :style="gainLoss >= 0 ? 'border-color:#bee3c7;background:#f0faf3' : 'border-color:#fecdca;background:#fef3f2'">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500" x-text="gainLoss >= 0 ? 'Laba Pelepasan Aset' : 'Rugi Pelepasan Aset'"></div>
                                <div class="text-[22px] font-bold mono mt-1" :style="gainLoss >= 0 ? 'color:#1f6a34' : 'color:#b42318'"
                                     x-text="(gainLoss >= 0 ? '+' : '') + fmt(gainLoss)"></div>
                            </div>
                            <div class="text-[11px] text-ink-500 text-right">
                                <div><span x-text="fmt(harga)"></span> <span class="text-ink-400">(jual)</span></div>
                                <div class="text-ink-400">− <span x-text="fmt(sel.book)"></span> (buku)</div>
                            </div>
                        </div>
                    </div>
                    <div class="card p-4">
                        <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Preview Jurnal · jenis=penjualan_aset</div>
                        <table class="w-full text-[11.5px] mono">
                            <tbody>
                                <tr><td class="py-1">1-1001 Kas &amp; Bank</td><td class="text-right" x-text="fmt(harga)"></td><td class="text-right">—</td></tr>
                                <tr><td class="py-1">1-2002 Akumulasi Penyusutan</td><td class="text-right" x-text="fmt(sel.accumulated)"></td><td class="text-right">—</td></tr>
                                <template x-if="gainLoss < 0">
                                    <tr><td class="py-1">7-1001 Rugi Pelepasan Aset</td><td class="text-right" x-text="fmt(-gainLoss)"></td><td class="text-right">—</td></tr>
                                </template>
                                <tr><td class="py-1 pl-4">1-2001 Aset Tetap - Genset</td><td class="text-right">—</td><td class="text-right" x-text="fmt(sel.harga)"></td></tr>
                                <template x-if="gainLoss > 0">
                                    <tr><td class="py-1 pl-4">7-1001 Laba Pelepasan Aset</td><td class="text-right">—</td><td class="text-right" x-text="fmt(gainLoss)"></td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
        <x-slot:footer>
            <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="!sel || !harga || saving" @click="lepasAset()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Memproses...' : 'Lepas Unit & Buat Jurnal'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
