@extends('layouts.app')

@section('content')
@php
    $sewaById = $d['sewaById'];
    $gensetById = $d['gensetById'];
    $penggunaById = $d['penggunaById'];
    $pelangganById = $d['pelangganById'];
    $akunByKode = $d['akunByKode'];

    $opexJournals = collect($d['jurnal_akuntansi'])->where('jenis_jurnal', 'beban_operasional');

    $meltedRows = collect($d['detail_sewa'])->map(function ($dd) use ($sewaById, $gensetById) {
        $s = $sewaById[$dd['id_sewa']] ?? null;
        $g = $gensetById[$dd['id_genset']];
        return [
            'kontrak' => $s['no_referensi_kontrak'] ?? '',
            'proyek' => $dd['alamat_proyek'],
            'genset' => $g['nomor_seri'],
            'operator' => $dd['biaya_operator'],
            'mobdemob' => $dd['biaya_mobdemob'],
            'bbm' => $dd['biaya_bbm'],
            'total' => $dd['biaya_operator'] + $dd['biaya_mobdemob'] + $dd['biaya_bbm'],
        ];
    });

    $bebanAkun = collect($d['akun_perkiraan'])->filter(fn ($a) => $a['kategori_akun'] === 'beban' && $a['sub_kategori'] !== 'header');
    $akunNames = collect($d['akun_perkiraan'])->mapWithKeys(fn ($a) => [$a['kode_akun'] => $a['nama_akun']]);

    $tabs = [
        ['id' => 'extra', 'label' => 'Biaya Tambahan (Jurnal Manual)', 'count' => $opexJournals->count()],
        ['id' => 'melt', 'label' => 'Biaya Melekat pada Sewa', 'count' => $meltedRows->count()],
    ];
@endphp

<div x-data="{
    tab: 'extra', createOpen: false, saving: false,
    form: { kode_akun: '5-3001', nominal: 0, tanggal: '2026-04-25', keterangan: '', id_sewa: '' },
    akun: @js($akunNames),
    fmt: window.fmtIDR,
    simpanBeban() {
        if (!this.form.nominal || !this.form.keterangan) { this.$store.toasts.push('Nominal & keterangan wajib diisi','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/opex', this.form,
            'Beban tercatat · jurnal Pengeluaran Kas dibuat.').catch(() => this.saving = false);
    },
}">
    <x-section-header title="Beban Operasional"
        subtitle="Pencatatan biaya operasional di luar transaksi sewa">
        <x-slot:actions>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Catat Beban</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-4 gap-3 mb-5">
        <x-stat-card label="Beban di Detail Sewa" :value="fmtIDR($meltedRows->sum('total'))" sub="operator + mobdemob + BBM melekat" tone="default" />
        <x-stat-card label="Beban Tambahan (Apr)" :value="fmtIDR($opexJournals->sum('total_debit'))" sub="beban operasional di luar sewa" tone="warn" />
        <x-stat-card label="Beban BBM" :value="fmtIDR($meltedRows->sum('bbm'))" tone="default" />
        <x-stat-card label="Beban Mobilisasi" :value="fmtIDR($meltedRows->sum('mobdemob'))" tone="default" />
    </div>

    <x-tab-bar :tabs="$tabs" />

    {{-- Tab: jurnal manual --}}
    <div x-show="tab === 'extra'" class="card overflow-hidden mt-3">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:140px">No. Bukti</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Tanggal</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Referensi Sewa</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Keterangan</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:140px">Total</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:130px">Dibuat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opexJournals as $j)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $j['no_bukti'] }}</td>
                        <td class="px-3 py-2.5">{{ fmtDate($j['tanggal']) }}</td>
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $j['referensi_id'] ? ($sewaById[$j['referensi_id']]['no_referensi_kontrak'] ?? '—') : '—' }}</td>
                        <td class="px-3 py-2.5">{{ $j['keterangan'] }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-medium mono">{{ fmtIDR($j['total_debit']) }}</span></td>
                        <td class="px-3 py-2.5">{{ $penggunaById[$j['dibuat_oleh']]['nama'] ?? 'Sistem' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tab: biaya melekat --}}
    <div x-show="tab === 'melt'" x-cloak class="card overflow-hidden mt-3">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold">Kontrak</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Genset</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Proyek</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Operator</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Mob-Demob</th>
                    <th class="px-3 py-2.5 text-right font-semibold">BBM</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:130px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meltedRows as $r)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $r['kontrak'] }}</td>
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $r['genset'] }}</td>
                        <td class="px-3 py-2.5"><span class="text-[12px] text-ink-600">{{ $r['proyek'] }}</span></td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($r['operator']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($r['mobdemob']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($r['bbm']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-semibold">{{ fmtIDR($r['total']) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== Create drawer ===== --}}
    <x-drawer show="createOpen" close="createOpen = false" :width="580"
        title="Catat Beban Operasional"
        subtitle="Jurnal Beban / Kas akan dibuat otomatis saat disimpan">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Tanggal" :required="true"><input type="date" class="inp" x-model="form.tanggal" /></x-form-field>
                <x-form-field label="Nominal" :required="true"><input type="number" class="inp" x-model="form.nominal" /></x-form-field>
            </div>
            <x-form-field label="Akun Beban" :required="true">
                <select class="inp" x-model="form.kode_akun">
                    @foreach ($bebanAkun as $a)
                        <option value="{{ $a['kode_akun'] }}">{{ $a['kode_akun'] }} · {{ $a['nama_akun'] }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <x-form-field label="Referensi Sewa (opsional)" hint="Pilih jika biaya melekat ke kontrak sewa tertentu">
                <select class="inp" x-model="form.id_sewa">
                    <option value="">— tidak terikat sewa —</option>
                    @foreach (collect($d['transaksi_sewa'])->where('status_pesanan', '!=', 'dibatalkan') as $s)
                        <option value="{{ $s['id_sewa'] }}">{{ $s['no_referensi_kontrak'] }} · {{ $pelangganById[$s['id_pelanggan']]['nama_perusahaan'] }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <x-form-field label="Keterangan" :required="true">
                <textarea class="inp" rows="3" x-model="form.keterangan" placeholder="Tambahan BBM untuk site Tenggarong, dll."></textarea>
            </x-form-field>
            <div class="card p-4 bg-ink-50 border-ink-200">
                <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Preview Jurnal</div>
                <table class="w-full text-[11.5px] mono">
                    <tbody>
                        <tr><td class="py-1"><span x-text="form.kode_akun"></span> <span x-text="akun[form.kode_akun]"></span></td><td class="text-right" x-text="fmt(Number(form.nominal)||0)"></td><td class="text-right">—</td></tr>
                        <tr><td class="py-1 pl-4">1-1001 Kas &amp; Bank</td><td class="text-right">—</td><td class="text-right" x-text="fmt(Number(form.nominal)||0)"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <x-slot:footer>
            <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanBeban()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
