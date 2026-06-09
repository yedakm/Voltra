@extends('layouts.app')

@section('content')
@php
    $pengembalian = collect($d['pengembalian']);
    $gensetById = $d['gensetById'];
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];
    $sewaById = $d['sewaById'];
    $penggunaById = $d['penggunaById'];
    $pelangganById = $d['pelangganById'];

    $tabs = [
        ['id' => 'all', 'label' => 'Semua', 'count' => $pengembalian->count()],
        ['id' => 'pengambilan', 'label' => 'Pengambilan (Kirim)', 'count' => $pengembalian->where('jenis_aktivitas', 'pengambilan')->count()],
        ['id' => 'pengembalian', 'label' => 'Pengembalian (Tarik)', 'count' => $pengembalian->where('jenis_aktivitas', 'pengembalian')->count()],
    ];
    $activeRentals = collect($d['transaksi_sewa'])->whereIn('status_pesanan', ['deal', 'pesan']);
    $diProyek = collect($d['genset'])->where('status', 'di_proyek')->count();

    // unit per kontrak — untuk selector genset di drawer serah-terima
    $unitsBySewa = [];
    foreach ($activeRentals as $s) {
        foreach ($d['detailSewaBySewa'][$s['id_sewa']] ?? [] as $dd) {
            $g = $gensetById[$dd['id_genset']];
            $unitsBySewa[$s['id_sewa']][] = [
                'id' => $g['id_genset'],
                'label' => $g['nomor_seri'] . ' · ' . $merekById[$g['id_merek']]['nama_merek'],
            ];
        }
    }
@endphp

<div x-data="{
    tab: 'all', open: null, createOpen: false, saving: false,
    units: @js($unitsBySewa),
    ho: { id_sewa: '', id_genset: '', jenis_aktivitas: 'pengambilan', tanggal: '2026-04-25T08:00',
          pic_dari_pelanggan: '', pic_dari_rental: '', kondisi_genset: '', catatan: '' },
    simpanHandover() {
        if (!this.ho.id_sewa || !this.ho.id_genset) { this.$store.toasts.push('Kontrak & unit wajib dipilih','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/handover', {
            ...this.ho, tanggal: this.ho.tanggal.replace('T',' ') + ':00',
        }, 'Serah-terima tercatat · status & lokasi genset diperbarui.').catch(() => this.saving = false);
    },
}">

    <x-section-header title="Serah-Terima Genset"
        subtitle="Pencatatan pengambilan & pengembalian unit — status dan lokasi terkini diperbarui otomatis">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Catat Serah-Terima</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-4 gap-3 mb-5">
        <x-stat-card label="Total Aktivitas (Apr)" :value="$pengembalian->count()" sub="serah-terima tercatat" />
        <x-stat-card label="Pengambilan (Kirim)" :value="$pengembalian->where('jenis_aktivitas', 'pengambilan')->count()" tone="brand" />
        <x-stat-card label="Pengembalian (Tarik)" :value="$pengembalian->where('jenis_aktivitas', 'pengembalian')->count()" tone="default" />
        <x-stat-card label="Unit Di Proyek" :value="$diProyek" sub="berdasarkan genset.status" tone="warn" />
    </div>

    <x-tab-bar :tabs="$tabs" />
    <x-toolbar placeholder="Cari nomor seri, kontrak..." />

    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:90px">ID</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Jenis</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:150px">Tanggal</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Kontrak</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Genset</th>
                    <th class="px-3 py-2.5 text-left font-semibold">PIC Rental → Pelanggan</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pengembalian as $h)
                    @php $g = $gensetById[$h['id_genset']]; @endphp
                    <tr class="border-b border-ink-100 hoverable cursor-pointer"
                        x-show="tab==='all' || tab==='{{ $h['jenis_aktivitas'] }}'"
                        @click="open = {{ $h['id_pengembalian'] }}">
                        <td class="px-3 py-2.5 mono text-[12px]">HO-{{ str_pad($h['id_pengembalian'], 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-3 py-2.5"><x-status-pill :status="$h['jenis_aktivitas']" /></td>
                        <td class="px-3 py-2.5">{{ fmtDateTime($h['tanggal']) }}</td>
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $sewaById[$h['id_sewa']]['no_referensi_kontrak'] ?? '' }}</td>
                        <td class="px-3 py-2.5">
                            <div class="font-medium text-[12.5px]">{{ $merekById[$g['id_merek']]['nama_merek'] }} {{ $kategoriById[$g['id_kategori']]['kapasitas'] }}</div>
                            <div class="mono text-[11px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                        </td>
                        <td class="px-3 py-2.5 text-[12px]">
                            <div>{{ $h['pic_dari_rental'] }}</div>
                            <div class="text-ink-500">→ {{ $h['pic_dari_pelanggan'] }}</div>
                        </td>
                        <td class="px-3 py-2.5"><span class="text-[12px] text-ink-600">{{ \Illuminate\Support\Str::limit($h['kondisi_genset'], 60) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Detail drawers --}}
    @foreach ($pengembalian as $h)
        @php
            $g = $gensetById[$h['id_genset']];
            $s = $sewaById[$h['id_sewa']] ?? null;
        @endphp
        <x-drawer show="open === {{ $h['id_pengembalian'] }}" close="open = null" :width="640"
            :title="'HO-'.str_pad($h['id_pengembalian'], 4, '0', STR_PAD_LEFT).' · '.lbl($h['jenis_aktivitas'])"
            :subtitle="($s['no_referensi_kontrak'] ?? '').' · '.$g['nomor_seri']">

            <div class="flex items-center gap-2 mb-4">
                <x-status-pill :status="$h['jenis_aktivitas']" />
                <span class="text-[12px] text-ink-500">{{ fmtDateTime($h['tanggal']) }}</span>
                <span class="ml-auto text-[11px] text-ink-400 mono">id #{{ $h['id_pengembalian'] }}</span>
            </div>

            <x-placeholder label="foto_kondisi: {{ $h['foto_kondisi'] }}" :height="220" />

            <div class="grid grid-cols-2 gap-4 mt-5 mb-5">
                <x-field label="PIC dari Rental">{{ $h['pic_dari_rental'] }}</x-field>
                <x-field label="PIC dari Pelanggan">{{ $h['pic_dari_pelanggan'] }}</x-field>
                <x-field label="Genset">
                    <div class="font-medium">{{ $merekById[$g['id_merek']]['nama_merek'] }} · {{ $kategoriById[$g['id_kategori']]['kapasitas'] }}</div>
                    <div class="mono text-[11px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                </x-field>
                <x-field label="Dicatat oleh">{{ $penggunaById[$h['dicatat_oleh']]['nama'] ?? '' }}</x-field>
            </div>

            <div class="mb-5">
                <x-field label="Kondisi Genset (deskripsi fisik)">{{ $h['kondisi_genset'] }}</x-field>
            </div>

            @if ($h['catatan'])
                <div class="mb-5">
                    <x-field label="Catatan Tambahan">{{ $h['catatan'] }}</x-field>
                </div>
            @endif

            <div class="card p-4 bg-ink-50 border-ink-200">
                <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Dampak Sistem</div>
                <div class="text-[12px] space-y-1">
                    @if ($h['jenis_aktivitas'] === 'pengambilan')
                        <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Status unit diubah ke "di proyek"</div>
                        <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Lokasi unit diperbarui ke alamat proyek</div>
                    @else
                        <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Status unit diubah ke "di gudang"</div>
                        <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Kalender unit dibebaskan untuk tanggal-tanggal setelahnya</div>
                    @endif
                </div>
            </div>

            <x-slot:footer>
                <button class="btn btn-ghost" @click="open = null">Tutup</button>
            </x-slot:footer>
        </x-drawer>
    @endforeach

    {{-- Create drawer --}}
    <x-drawer show="createOpen" close="createOpen = false" :width="620"
        title="Catat Serah-Terima" subtitle="Pilih kontrak → jenis aktivitas → input PIC & kondisi">

        <div class="space-y-4">
            <x-form-field label="Jenis Aktivitas" :required="true">
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['pengambilan' => 'Kirim unit ke proyek', 'pengembalian' => 'Tarik unit kembali ke gudang'] as $j => $desc)
                        <button type="button" @click="ho.jenis_aktivitas = '{{ $j }}'"
                            class="p-3 rounded border text-left"
                            :class="ho.jenis_aktivitas === '{{ $j }}' ? 'border-brand-500 bg-brand-50' : 'border-ink-200'">
                            <div class="font-medium text-[13px] capitalize">{{ $j }}</div>
                            <div class="text-[11px] text-ink-500 mt-0.5">{{ $desc }}</div>
                        </button>
                    @endforeach
                </div>
            </x-form-field>

            <x-form-field label="Transaksi Sewa" :required="true">
                <select class="inp" x-model="ho.id_sewa" @change="ho.id_genset = ''">
                    <option value="">— pilih kontrak —</option>
                    @foreach ($activeRentals as $s)
                        <option value="{{ $s['id_sewa'] }}">{{ $s['no_referensi_kontrak'] }} · {{ $pelangganById[$s['id_pelanggan']]['nama_perusahaan'] }}</option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field label="Unit Genset" :required="true">
                <select class="inp" x-model="ho.id_genset">
                    <option value="">— pilih unit —</option>
                    <template x-for="u in (units[ho.id_sewa] || [])" :key="u.id">
                        <option :value="u.id" x-text="u.label"></option>
                    </template>
                </select>
            </x-form-field>

            <x-form-field label="Tanggal & Waktu" :required="true">
                <input type="datetime-local" class="inp" x-model="ho.tanggal" />
            </x-form-field>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="PIC dari Pelanggan" :required="true">
                    <input class="inp" x-model="ho.pic_dari_pelanggan" placeholder="Nama yang serah/terima dari pelanggan" />
                </x-form-field>
                <x-form-field label="PIC dari Rental" :required="true">
                    <input class="inp" x-model="ho.pic_dari_rental" placeholder="Petugas rental yang antar/jemput" />
                </x-form-field>
            </div>

            <x-form-field label="Kondisi Genset" :required="true" hint="Deskripsi fisik & operasional saat serah-terima">
                <textarea class="inp" rows="3" x-model="ho.kondisi_genset" placeholder="Cat baret kiri, indikator semua normal, BBM 3/4 tangki..."></textarea>
            </x-form-field>

            <x-form-field label="Foto Kondisi" hint="Akan disimpan ke kolom foto_kondisi">
                <x-placeholder label="klik / drop untuk upload foto" :height="100" />
            </x-form-field>

            <x-form-field label="Catatan Tambahan">
                <textarea class="inp" rows="2" x-model="ho.catatan"></textarea>
            </x-form-field>
        </div>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanHandover()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
