@extends('layouts.app')

@section('content')
@php
    use App\Support\VoltraData;

    $genset = collect($d['genset']);
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];
    $supplierById = $d['supplierById'];
    $penggunaById = $d['penggunaById'];
    $sewaById = $d['sewaById'];
    $pelangganById = $d['pelangganById'];

    $tabs = [
        ['id' => 'all', 'label' => 'Semua', 'count' => $genset->count()],
        ['id' => 'di_proyek', 'label' => 'Di Proyek', 'count' => $genset->where('status', 'di_proyek')->count()],
        ['id' => 'di_gudang', 'label' => 'Di Gudang', 'count' => $genset->where('status', 'di_gudang')->count()],
        ['id' => 'rusak', 'label' => 'Rusak', 'count' => $genset->where('status', 'rusak')->count()],
        ['id' => 'terjual', 'label' => 'Terjual', 'count' => $genset->where('status', 'terjual')->count()],
    ];

    $active = $genset->where('status', '!=', 'terjual');
    $totalValue = $active->sum('harga_perolehan');
    $totalAccum = $active->sum(fn ($g) => VoltraData::depresiasiInfo($g)['accumulated']);
    $totalBook = $totalValue - $totalAccum;
@endphp

<div x-data="{
    tab: 'all', search: '', open: null, createOpen: false, kategoriOpen: false, saving: false,
    purchase: { id_kategori: '', id_merek: '', id_supplier: '', nomor_seri: '', tgl_perolehan: '2026-04-25', harga_perolehan: 0, nilai_residu_aktual: 0, umur_ekonomis_aktual: 96, metode_bayar: 'kas' },
    kategori: { kapasitas: '', umur_ekonomis_default: 96, estimasi_nilai_residu: 0 },
    simpanAset() {
        if (!this.purchase.id_kategori || !this.purchase.nomor_seri || !this.purchase.harga_perolehan) {
            this.$store.toasts.push('Kategori, nomor seri, & harga wajib diisi','error'); return;
        }
        this.saving = true;
        window.voltraSave('/aksi/asset-purchase', this.purchase,
            r => 'Genset ' + r.genset.nomor_seri + ' tersimpan · jurnal pembelian di-post.').catch(() => this.saving = false);
    },
    simpanKategori() {
        if (!this.kategori.kapasitas || !this.kategori.umur_ekonomis_default) {
            this.$store.toasts.push('Kapasitas & umur ekonomis wajib diisi','error'); return;
        }
        this.saving = true;
        window.voltraSave('/aksi/master/kategori-genset', this.kategori,
            r => 'Kategori ' + r.data.kapasitas + ' tersimpan.').catch(() => this.saving = false);
    },
    ubahStatus(idGenset, status, konfirmasi) {
        if (konfirmasi && !confirm(konfirmasi)) return;
        this.saving = true;
        window.voltraSave('/aksi/genset/' + idGenset + '/status', { status },
            r => 'Status unit diubah ke ' + r.genset.status.replace('_',' ') + '.').catch(() => this.saving = false);
    },
}">

    <x-section-header title="Aset Genset"
        subtitle="Daftar unit genset milik perusahaan · penyusutan dihitung otomatis tiap bulan">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
            <button class="btn btn-ghost" @click="kategoriOpen = true"><x-icon name="tag" :size="14" /> Kategori</button>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Pembelian Aset</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-4 gap-3 mb-5">
        <x-stat-card label="Total Aset Aktif" value="{{ $active->count() }} unit" sub="{{ count($d['kategori_genset']) }} kategori · {{ count($d['merek']) }} merek" icon="asset" />
        <x-stat-card label="Nilai Perolehan" :value="fmtIDR($totalValue)" tone="default" icon="tag" />
        <x-stat-card label="Akumulasi Penyusutan" :value="fmtIDR($totalAccum)" tone="danger" />
        <x-stat-card label="Nilai Buku Saat Ini" :value="fmtIDR($totalBook)" tone="brand" />
    </div>

    <x-tab-bar :tabs="$tabs" />
    <x-toolbar searchModel="search" placeholder="Cari nomor seri, merek...">
        <x-slot:filters>
            <button class="btn btn-ghost"><x-icon name="filter" :size="14" /> Filter kategori</button>
        </x-slot:filters>
    </x-toolbar>

    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:150px">Nomor Seri</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Merek &amp; Kategori</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:120px">Tgl. Perolehan</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:150px">Harga Perolehan</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:140px">Nilai Buku</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:200px">Lokasi Terkini</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($genset as $g)
                    @php
                        $info = VoltraData::depresiasiInfo($g);
                        $searchText = strtolower($g['nomor_seri'] . ' ' . $merekById[$g['id_merek']]['nama_merek']);
                    @endphp
                    <tr class="border-b border-ink-100 hoverable cursor-pointer"
                        x-show="(tab==='all' || tab==='{{ $g['status'] }}') && (search==='' || @js($searchText).includes(search.toLowerCase()))"
                        @click="open = {{ $g['id_genset'] }}">
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $g['nomor_seri'] }}</td>
                        <td class="px-3 py-2.5">
                            <div class="font-medium text-ink-800">{{ $merekById[$g['id_merek']]['nama_merek'] }} · {{ $kategoriById[$g['id_kategori']]['kapasitas'] }}</div>
                            <div class="text-[11px] text-ink-500">{{ $supplierById[$g['id_supplier']]['nama_supplier'] }}</div>
                        </td>
                        <td class="px-3 py-2.5">{{ fmtDate($g['tgl_perolehan']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($g['harga_perolehan']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-medium">{{ fmtIDR($info['bookValue']) }}</span></td>
                        <td class="px-3 py-2.5"><span class="text-[12px] text-ink-600">{{ $g['lokasi_terkini'] }}</span></td>
                        <td class="px-3 py-2.5"><x-status-pill :status="$g['status']" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== Asset detail drawers ===== --}}
    @foreach ($genset as $g)
        @php
            $k = $kategoriById[$g['id_kategori']];
            $sup = $supplierById[$g['id_supplier']];
            $mer = $merekById[$g['id_merek']];
            $info = VoltraData::depresiasiInfo($g);
            $servis = collect($d['pemeliharaan'])->where('id_genset', $g['id_genset']);
            $sewas = collect($d['detail_sewa'])->where('id_genset', $g['id_genset']);
            $depreciable = $g['harga_perolehan'] - $g['nilai_residu_aktual'];
            $pctDone = $depreciable ? round($info['accumulated'] / $depreciable * 100) : 0;

            // depreciation schedule rows
            $rowCount = min($info['monthsElapsed'] + 3, 24);
            $schedStart = \DateTime::createFromFormat('Y-m-d', $g['tgl_perolehan']);
            $schedStart->modify('first day of this month');
            $schedRows = [];
            for ($i = 0; $i < $rowCount; $i++) {
                $dt = (clone $schedStart)->modify("+$i month");
                $accum = min($info['monthly'] * ($i + 1), $depreciable);
                $schedRows[] = [
                    'period' => $dt->format('Y-m'),
                    'accum' => $accum,
                    'book' => $g['harga_perolehan'] - $accum,
                    'status' => $i < $info['monthsElapsed'] ? 'posted' : 'pending',
                ];
            }
        @endphp
        <x-drawer show="open === {{ $g['id_genset'] }}" close="open = null" :width="700"
            :title="$mer['nama_merek'].' · '.$k['kapasitas']">
            <x-slot:subtitle><span class="mono">{{ $g['nomor_seri'] }}</span></x-slot:subtitle>

            <div x-data="{ atab: 'umum' }">
                <div class="flex items-center gap-2 mb-4">
                    <x-status-pill :status="$g['status']" />
                    <div class="ml-auto text-[11px] text-ink-400 mono">id_genset #{{ $g['id_genset'] }}</div>
                </div>

                <div class="border-b border-ink-200 flex items-center gap-6 px-1">
                    @foreach ([['umum', 'Informasi Umum', null], ['depresiasi', 'Jadwal Penyusutan', $info['monthsElapsed']], ['riwayat', 'Riwayat Sewa', $sewas->count()], ['servis', 'Riwayat Servis', $servis->count()]] as $t)
                        <div class="tab" :class="{ 'active': atab === '{{ $t[0] }}' }" @click="atab = '{{ $t[0] }}'">
                            {{ $t[1] }}@if ($t[2] !== null)<span class="ml-1.5 text-[11px] text-ink-400">({{ $t[2] }})</span>@endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{-- Umum --}}
                    <div x-show="atab === 'umum'" class="space-y-5">
                        <x-placeholder label="foto unit (kolom genset.foto)" :height="180" />
                        <div class="grid grid-cols-2 gap-4">
                            <x-field label="Kategori">{{ $k['kapasitas'] }}</x-field>
                            <x-field label="Merek (global)">{{ $mer['nama_merek'] }} <span class="text-ink-400 text-[11px]">· {{ $mer['negara_asal'] }}</span></x-field>
                            <x-field label="Supplier">{{ $sup['nama_supplier'] }}</x-field>
                            <x-field label="Tgl. Perolehan">{{ fmtDate($g['tgl_perolehan']) }}</x-field>
                            <x-field label="Harga Perolehan"><span class="mono">{{ fmtIDR($g['harga_perolehan']) }}</span></x-field>
                            <x-field label="Nilai Residu (aktual)" hint="Default kategori: {{ fmtIDR($k['estimasi_nilai_residu']) }}"><span class="mono">{{ fmtIDR($g['nilai_residu_aktual']) }}</span></x-field>
                            <x-field label="Umur Ekonomis (aktual)" hint="Default kategori: {{ $k['umur_ekonomis_default'] }} bulan">{{ $g['umur_ekonomis_aktual'] }} bulan</x-field>
                            <x-field label="Umur Berjalan">{{ $info['monthsElapsed'] }} bulan</x-field>
                            <div class="col-span-2">
                                <x-field label="Lokasi Terkini (diinput manual)" hint="Diupdate via Serah-Terima — bukan GPS real-time">
                                    <div class="font-medium">{{ $g['lokasi_terkini'] }}</div>
                                </x-field>
                            </div>
                            <div class="col-span-2"><x-field label="Deskripsi">{{ $g['deskripsi'] }}</x-field></div>
                        </div>
                        <div class="card p-4 bg-ink-50 border-ink-200">
                            <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-3">Perhitungan Penyusutan</div>
                            <div class="grid grid-cols-4 gap-4 text-[12.5px]">
                                <div><div class="text-ink-500">Beban / Bulan</div><div class="font-semibold mono mt-0.5">{{ fmtIDR($info['monthly']) }}</div></div>
                                <div><div class="text-ink-500">Akumulasi</div><div class="font-semibold mono mt-0.5 text-red-700">{{ fmtIDR($info['accumulated']) }}</div></div>
                                <div><div class="text-ink-500">Nilai Buku</div><div class="font-semibold mono mt-0.5 text-brand-700">{{ fmtIDR($info['bookValue']) }}</div></div>
                                <div><div class="text-ink-500">% Tersusutkan</div><div class="font-semibold mt-0.5">{{ $pctDone }}%</div></div>
                            </div>
                            <div class="mt-3 text-[11px] text-ink-500 mono">Rumus: ({{ fmtIDR($g['harga_perolehan']) }} − {{ fmtIDR($g['nilai_residu_aktual']) }}) ÷ {{ $g['umur_ekonomis_aktual'] }} bulan</div>
                        </div>
                    </div>

                    {{-- Depresiasi --}}
                    <div x-show="atab === 'depresiasi'" x-cloak class="card overflow-hidden">
                        <table class="w-full text-[12.5px]">
                            <thead class="bg-ink-50 text-[10.5px] uppercase text-ink-500 tracking-wider">
                                <tr>
                                    <th class="text-left px-3 py-2 font-semibold">Periode</th>
                                    <th class="text-right px-3 py-2 font-semibold">Beban</th>
                                    <th class="text-right px-3 py-2 font-semibold">Akumulasi</th>
                                    <th class="text-right px-3 py-2 font-semibold">Nilai Buku</th>
                                    <th class="text-left px-3 py-2 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedRows as $sr)
                                    <tr class="border-t border-ink-100">
                                        <td class="px-3 py-1.5 mono">{{ $sr['period'] }}</td>
                                        <td class="px-3 py-1.5 text-right mono">{{ fmtIDR($info['monthly']) }}</td>
                                        <td class="px-3 py-1.5 text-right mono text-red-700">{{ fmtIDR($sr['accum']) }}</td>
                                        <td class="px-3 py-1.5 text-right mono">{{ fmtIDR($sr['book']) }}</td>
                                        <td class="px-3 py-1.5"><x-status-pill :status="$sr['status']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Riwayat Sewa --}}
                    <div x-show="atab === 'riwayat'" x-cloak class="space-y-2">
                        @if ($sewas->isEmpty())
                            <x-empty-state title="Belum ada riwayat sewa" />
                        @endif
                        @foreach ($sewas as $ds)
                            @php $s = $sewaById[$ds['id_sewa']] ?? null; @endphp
                            <div class="card p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="mono text-[12px]">{{ $s['no_referensi_kontrak'] ?? '' }}</div>
                                        <div class="text-[12px] text-ink-700">{{ $s ? ($pelangganById[$s['id_pelanggan']]['nama_perusahaan'] ?? '') : '' }}</div>
                                        <div class="text-[11px] text-ink-500">{{ fmtDateShort($ds['start_date']) }} – {{ fmtDateShort($ds['end_date']) }} · {{ $ds['alamat_proyek'] }}</div>
                                    </div>
                                    @if ($s)<x-status-pill :status="$s['status_pesanan']" />@endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Riwayat Servis --}}
                    <div x-show="atab === 'servis'" x-cloak class="space-y-2">
                        @if ($servis->isEmpty())
                            <x-empty-state title="Belum ada riwayat servis" />
                        @endif
                        @foreach ($servis as $p)
                            <div class="card p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="text-[12.5px] font-medium">{{ lbl($p['jenis_servis']) }} · {{ fmtDate($p['tgl_mulai_servis']) }}</div>
                                    <x-status-pill :status="$p['jenis_servis']" />
                                </div>
                                <div class="text-[11.5px] text-ink-500">Teknisi: {{ $penggunaById[$p['id_pengguna']]['nama'] ?? '' }}</div>
                                <div class="text-[11.5px] text-ink-500">Jasa eksternal: {{ fmtIDR($p['biaya_jasa_eksternal']) }}</div>
                                <div class="text-[11.5px] text-ink-400 mt-1">{{ $p['keterangan'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <button class="btn btn-ghost" @click="open = null">Tutup</button>
                <button class="btn btn-ghost" @click="window.print()"><x-icon name="doc" :size="14" /> Cetak</button>
                @if (in_array($g['status'], ['di_gudang', 'di_proyek']))
                    <button class="btn btn-ghost text-red-700" :disabled="saving"
                            @click="ubahStatus({{ $g['id_genset'] }}, 'rusak', 'Tandai unit {{ $g['nomor_seri'] }} sebagai RUSAK? Unit akan dipindahkan ke Workshop.')">
                        <x-icon name="warn" :size="14" /> Tandai Rusak
                    </button>
                @elseif ($g['status'] === 'rusak')
                    <button class="btn btn-ghost text-emerald-700" :disabled="saving"
                            @click="ubahStatus({{ $g['id_genset'] }}, 'di_gudang', 'Unit {{ $g['nomor_seri'] }} sudah selesai diperbaiki dan siap dipakai lagi?')">
                        <x-icon name="check" :size="14" /> Kembalikan ke Gudang
                    </button>
                @endif
                @if ($g['status'] !== 'terjual')
                    <a href="{{ route('disposal') }}" class="btn btn-danger"><x-icon name="logout" :size="14" /> Pelepasan Aset</a>
                @endif
            </x-slot:footer>
        </x-drawer>
    @endforeach

    {{-- ===== Purchase drawer ===== --}}
    <x-drawer show="createOpen" close="createOpen = false" :width="620"
        title="Pembelian Genset Baru"
        subtitle="Aset masuk — jurnal Pembelian Aset Tetap akan ter-generate otomatis">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Kategori" :required="true">
                    <select class="inp" x-model="purchase.id_kategori">
                        <option value="">— pilih —</option>
                        @foreach ($d['kategori_genset'] as $k)<option value="{{ $k['id_kategori'] }}">{{ $k['kapasitas'] }}</option>@endforeach
                    </select>
                </x-form-field>
                <x-form-field label="Merek (global)" :required="true">
                    <select class="inp" x-model="purchase.id_merek">
                        <option value="">— pilih —</option>
                        @foreach ($d['merek'] as $m)<option value="{{ $m['id_merek'] }}">{{ $m['nama_merek'] }}</option>@endforeach
                    </select>
                </x-form-field>
            </div>
            <x-form-field label="Supplier" :required="true">
                <select class="inp" x-model="purchase.id_supplier">
                    <option value="">— pilih —</option>
                    @foreach ($d['supplier'] as $s)<option value="{{ $s['id_supplier'] }}">{{ $s['nama_supplier'] }}</option>@endforeach
                </select>
            </x-form-field>
            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Nomor Seri" :required="true"><input class="inp" x-model="purchase.nomor_seri" placeholder="e.g. CMN-250-0233" /></x-form-field>
                <x-form-field label="Tgl. Perolehan" :required="true"><input type="date" class="inp" x-model="purchase.tgl_perolehan" /></x-form-field>
                <x-form-field label="Harga Perolehan" :required="true"><input type="number" class="inp" x-model="purchase.harga_perolehan" /></x-form-field>
                <x-form-field label="Nilai Residu (aktual)"><input type="number" class="inp" x-model="purchase.nilai_residu_aktual" placeholder="default dari kategori" /></x-form-field>
                <x-form-field label="Umur Ekonomis (bulan)"><input type="number" class="inp" x-model="purchase.umur_ekonomis_aktual" placeholder="default dari kategori" /></x-form-field>
                <x-form-field label="Metode Bayar">
                    <select class="inp" x-model="purchase.metode_bayar">
                        <option value="kas">Tunai / Kas</option>
                        <option value="utang">Utang Supplier</option>
                    </select>
                </x-form-field>
            </div>
            <x-form-field label="Deskripsi"><textarea class="inp" rows="2"></textarea></x-form-field>
            <div class="card p-4 bg-ink-50 border-ink-200">
                <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Yang Akan Dijalankan</div>
                <div class="text-[12px] space-y-1">
                    <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Unit baru masuk daftar genset dengan status "di gudang"</div>
                    <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Kalender ketersediaan otomatis terisi (status: tersedia)</div>
                    <div class="flex items-center gap-2"><x-icon name="check" :size="12" class="text-emerald-600" /> Jurnal pembelian aset tetap dibuat otomatis</div>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanAset()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan & Buat Jurnal'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>

    {{-- ===== Kategori drawer ===== --}}
    <x-drawer show="kategoriOpen" close="kategoriOpen = false" :width="560"
        title="Kategori Genset"
        subtitle="Kelompok unit per kapasitas · jadi default umur ekonomis & nilai residu saat pembelian">

        <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Daftar Kategori ({{ count($d['kategori_genset']) }})</div>
        <div class="card overflow-hidden mb-5">
            @if (count($d['kategori_genset']) === 0)
                <div class="p-4 text-[12.5px] text-ink-500 italic">Belum ada kategori. Tambahkan minimal satu untuk bisa input pembelian genset.</div>
            @else
                <table class="w-full text-[12.5px]">
                    <thead>
                        <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                            <th class="px-3 py-2 text-left font-semibold">Kapasitas</th>
                            <th class="px-3 py-2 text-right font-semibold">Umur (bln)</th>
                            <th class="px-3 py-2 text-right font-semibold">Default Residu</th>
                            <th class="px-3 py-2 text-right font-semibold">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($d['kategori_genset'] as $k)
                            @php $unitCount = collect($d['genset'])->where('id_kategori', $k['id_kategori'])->count(); @endphp
                            <tr class="border-b border-ink-100">
                                <td class="px-3 py-2 font-medium">{{ $k['kapasitas'] }}</td>
                                <td class="px-3 py-2 text-right mono">{{ $k['umur_ekonomis_default'] }}</td>
                                <td class="px-3 py-2 text-right mono">{{ fmtIDR($k['estimasi_nilai_residu']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $unitCount }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Tambah Kategori Baru</div>
        <div class="space-y-4">
            <x-form-field label="Kapasitas" :required="true" hint="Mis. '250 kVA', '500 kVA'">
                <input class="inp" x-model="kategori.kapasitas" placeholder="250 kVA" />
            </x-form-field>
            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Umur Ekonomis Default" :required="true" hint="Dalam bulan (96 = 8 tahun)">
                    <input type="number" min="1" class="inp" x-model.number="kategori.umur_ekonomis_default" />
                </x-form-field>
                <x-form-field label="Estimasi Nilai Residu" :required="true" hint="~10–15% harga beli">
                    <input type="number" min="0" class="inp" x-model.number="kategori.estimasi_nilai_residu" />
                </x-form-field>
            </div>
            <div class="card p-3 bg-brand-50 border-brand-200 text-[11.5px] text-brand-900">
                <div class="font-semibold mb-0.5">Cara dipakai</div>
                Saat input pembelian unit baru, pilih kategori ini → form auto-isi umur &amp; residu sebagai default (boleh di-override per unit).
            </div>
        </div>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="kategoriOpen = false">Tutup</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanKategori()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Kategori'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
