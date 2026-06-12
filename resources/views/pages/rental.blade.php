@extends('layouts.app')

@section('content')
@php
    use App\Support\VoltraData;

    $transaksi = collect($d['transaksi_sewa']);
    $pelangganById = $d['pelangganById'];
    $gensetById = $d['gensetById'];
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];
    $penggunaById = $d['penggunaById'];
    $detailSewaBySewa = $d['detailSewaBySewa'];

    $tabs = [
        ['id' => 'all', 'label' => 'Semua', 'count' => $transaksi->count()],
        ['id' => 'aktif', 'label' => 'Aktif', 'count' => $transaksi->where('status_pesanan', 'deal')->count()],
        ['id' => 'pesan', 'label' => 'Pesanan', 'count' => $transaksi->where('status_pesanan', 'pesan')->count()],
        ['id' => 'selesai', 'label' => 'Selesai', 'count' => $transaksi->where('status_pesanan', 'selesai')->count()],
        ['id' => 'dibatalkan', 'label' => 'Dibatalkan', 'count' => $transaksi->where('status_pesanan', 'dibatalkan')->count()],
    ];

    $availableGensets = collect($d['genset'])->where('status', 'di_gudang')->values();
@endphp

<div x-data="{
    tab: 'all', search: '', open: null, createOpen: false, saving: false,
    step: 1,
    form: { id_pelanggan: '', id_genset: '', satuan_sewa: 'harian', kena_ppn: true, kena_pph: false, start_date: '2026-04-25', end_date: '2026-05-05', alamat_proyek: '', harga_sewa_unit: 0, biaya_operator: 0, biaya_mobdemob: 0, biaya_bbm: 0 },
    simpanKontrak() {
        if (!this.form.id_pelanggan || !this.form.id_genset) { this.$store.toasts.push('Pelanggan & unit wajib dipilih','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/rental', {
            id_pelanggan: this.form.id_pelanggan,
            satuan_sewa: this.form.satuan_sewa,
            kena_ppn: this.form.kena_ppn,
            kena_pph: this.form.kena_pph,
            items: [{
                id_genset: this.form.id_genset, start_date: this.form.start_date, end_date: this.form.end_date,
                alamat_proyek: this.form.alamat_proyek, harga_sewa_unit: Number(this.form.harga_sewa_unit||0),
                biaya_operator: Number(this.form.biaya_operator||0), biaya_mobdemob: Number(this.form.biaya_mobdemob||0),
                biaya_bbm: Number(this.form.biaya_bbm||0),
            }],
        }, r => 'Kontrak ' + r.sewa.no_invoice + ' tersimpan · jurnal dibuat.').catch(() => this.saving = false);
    },
    bayar(idSewa, sisa) {
        if (sisa <= 0) return;
        this.saving = true;
        window.voltraSave('/aksi/payment', { id_sewa: idSewa, nominal_bayar: sisa, metode_bayar: 'transfer' },
            'Pembayaran tercatat · jurnal Kas/Piutang dibuat.').catch(() => this.saving = false);
    },
    get days() { return window.dayCount(this.form.start_date, this.form.end_date); },
    get qty() { return this.form.satuan_sewa === 'bulanan' ? Math.max(1, Math.ceil(this.days/30)) : this.days; },
    get satuanLabel() { return this.form.satuan_sewa === 'bulanan' ? 'bulan' : 'hari'; },
    get sewaPokok() { return Number(this.form.harga_sewa_unit||0) * this.qty; },
    get sub() { return this.sewaPokok + Number(this.form.biaya_operator||0) + Number(this.form.biaya_mobdemob||0) + Number(this.form.biaya_bbm||0); },
    get ppn() { return this.form.kena_ppn ? Math.round(this.sub*0.11) : 0; },
    get pph() { return this.form.kena_pph ? Math.round(this.sub*0.02) : 0; },
    get totalTagihan() { return this.sub + this.ppn - this.pph; },
    fmt: window.fmtIDR,
}" x-init="$watch('createOpen', v => { if (v) step = 1; })">

    <x-section-header title="Penyewaan" subtitle="Kontrak sewa dan invoice. Jurnal akuntansi dibuat otomatis saat invoice terbit.">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Sewa Baru</button>
        </x-slot:actions>
    </x-section-header>

    <x-tab-bar :tabs="$tabs" />

    <x-toolbar searchModel="search" placeholder="Cari no. kontrak, invoice, pelanggan...">
        <x-slot:filters>
            <button class="btn btn-ghost"><x-icon name="filter" :size="14" /> Filter</button>
            <button class="btn btn-ghost">Periode Apr 2026 <x-icon name="chevron" :size="12" /></button>
        </x-slot:filters>
    </x-toolbar>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:160px">Kontrak / Invoice</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Pelanggan</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Unit</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:130px">Periode</th>
                        <th class="px-3 py-2.5 text-right font-semibold" style="width:140px">Total</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Pesanan</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaksi as $s)
                        @php
                            $cust = $pelangganById[$s['id_pelanggan']];
                            $det = $detailSewaBySewa[$s['id_sewa']] ?? [];
                            $rowTab = $s['status_pesanan'] === 'deal' ? 'aktif' : $s['status_pesanan'];
                            $searchText = strtolower($s['no_referensi_kontrak'] . ' ' . $s['no_invoice'] . ' ' . $cust['nama_perusahaan']);
                        @endphp
                        <tr class="border-b border-ink-100 hoverable cursor-pointer"
                            x-show="(tab==='all' || tab==='{{ $rowTab }}') && (search==='' || @js($searchText).includes(search.toLowerCase()))"
                            @click="open = {{ $s['id_sewa'] }}">
                            <td class="px-3 py-2.5">
                                <div class="mono text-[12px] font-medium">{{ $s['no_referensi_kontrak'] }}</div>
                                <div class="mono text-[11px] text-ink-500">{{ $s['no_invoice'] }}</div>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium text-ink-800">{{ $cust['nama_perusahaan'] }}</div>
                                <div class="text-[11px] text-ink-500">{{ $cust['pic_kontak'] }}</div>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="space-y-0.5">
                                    @foreach ($det as $dd)
                                        <div class="mono text-[11.5px] text-ink-600">{{ $gensetById[$dd['id_genset']]['nomor_seri'] ?? '' }}</div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                @if (isset($det[0]))
                                    <span class="text-[12px] text-ink-600">{{ fmtDateShort($det[0]['start_date']) }} – {{ fmtDateShort($det[0]['end_date']) }}</span>
                                @else — @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-medium">{{ fmtIDR($s['total_tagihan'] + $s['pajak']) }}</span></td>
                            <td class="px-3 py-2.5"><x-status-pill :status="$s['status_pesanan']" /></td>
                            <td class="px-3 py-2.5"><x-status-pill :status="$s['status_pembayaran']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Detail drawers (one per sewa) ===== --}}
    @foreach ($transaksi as $s)
        @php
            $cust = $pelangganById[$s['id_pelanggan']];
            $det = $detailSewaBySewa[$s['id_sewa']] ?? [];
            $pays = collect($d['pembayaran'])->where('id_sewa', $s['id_sewa']);
            $handovers = collect($d['pengembalian'])->where('id_sewa', $s['id_sewa']);
            $o = VoltraData::sewaOutstanding($s);
            $satuanS = $s['satuan_sewa'] ?? 'harian';
            $satuanLbl = $satuanS === 'bulanan' ? 'bulan' : 'hari';
        @endphp
        <x-drawer show="open === {{ $s['id_sewa'] }}" close="open = null" :width="680"
            :title="$s['no_referensi_kontrak']"
            :subtitle="$cust['nama_perusahaan'].' · Pesan '.fmtDate($s['tgl_pemesanan'])">

            <div class="flex items-center gap-2 mb-4">
                <x-status-pill :status="$s['status_pesanan']" />
                <x-status-pill :status="$s['status_pembayaran']" />
                <div class="ml-auto text-[11px] text-ink-400 mono">id_sewa #{{ $s['id_sewa'] }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <x-field label="Pelanggan">
                    <div class="font-medium">{{ $cust['nama_perusahaan'] }}</div>
                    <div class="text-[12px] text-ink-500">{{ $cust['pic_kontak'] }} · {{ $cust['no_telepon'] }}</div>
                    <div class="text-[11px] text-ink-400 mt-0.5">{{ $cust['alamat_lengkap'] }}</div>
                </x-field>
                <x-field label="No. Invoice (tersinkron)">
                    <div class="mono">{{ $s['no_invoice'] }}</div>
                    <div class="text-[11px] text-ink-500 mt-0.5">Terbit {{ fmtDate($s['tgl_terbit_invoice']) }} · J/T {{ fmtDate($s['tgl_jatuh_tempo']) }}</div>
                </x-field>
                <x-field label="NPWP"><span class="mono">{{ $cust['npwp'] }}</span></x-field>
                <x-field label="Dibuat oleh">{{ $penggunaById[$s['id_pengguna']]['nama'] ?? '' }} <span class="text-ink-400 text-[11px] capitalize">({{ $penggunaById[$s['id_pengguna']]['role'] ?? '' }})</span></x-field>
            </div>

            <div class="mb-5">
                <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Detail Unit yang Disewa</div>
                <div class="card overflow-hidden">
                    <table class="w-full text-[12.5px]">
                        <thead>
                            <tr class="bg-ink-50 text-[10.5px] uppercase text-ink-500 tracking-wider">
                                <th class="text-left px-3 py-2 font-semibold">Genset</th>
                                <th class="text-left px-3 py-2 font-semibold">Periode</th>
                                <th class="text-right px-3 py-2 font-semibold">Harga/{{ $satuanLbl }}</th>
                                <th class="text-right px-3 py-2 font-semibold">Mob-Demob</th>
                                <th class="text-right px-3 py-2 font-semibold">Op+BBM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($det as $dd)
                                @php
                                    $g = $gensetById[$dd['id_genset']];
                                    $k = $kategoriById[$g['id_kategori']] ?? $kategoriById[''];
                                    $days = max(1, (int) ceil((strtotime($dd['end_date']) - strtotime($dd['start_date'])) / 86400));
                                    $qty = $satuanS === 'bulanan' ? max(1, (int) ceil($days / 30)) : $days;
                                @endphp
                                <tr class="border-t border-ink-100">
                                    <td class="px-3 py-2.5">
                                        <div class="font-medium text-ink-800">{{ ($merekById[$g['id_merek']]['nama_merek'] ?? '—') }} · {{ $k['kapasitas'] }}</div>
                                        <div class="mono text-[11px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                                        <div class="text-[11px] text-ink-400 mt-0.5">📍 {{ $dd['alamat_proyek'] }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-ink-700">
                                        {{ fmtDateShort($dd['start_date']) }} – {{ fmtDateShort($dd['end_date']) }}
                                        <div class="text-[11px] text-ink-500 mt-0.5">{{ $qty }} {{ $satuanLbl }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($dd['harga_sewa_unit']) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($dd['biaya_mobdemob']) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($dd['biaya_operator'] + $dd['biaya_bbm']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-5">
                <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Tagihan &amp; Pembayaran</div>
                <div class="card p-4">
                    <div class="grid grid-cols-4 gap-4 text-[12px] pb-3 border-b border-ink-100">
                        <div><div class="text-ink-500">Subtotal (DPP)</div><div class="font-medium tabular-nums">{{ fmtIDR($s['total_tagihan']) }}</div></div>
                        <div><div class="text-ink-500">{{ ($s['kena_ppn'] ?? true) ? 'PPN 11%' : 'Tanpa PPN' }}</div><div class="font-medium tabular-nums">{{ $s['pajak'] > 0 ? '+ ' . fmtIDR($s['pajak']) : '—' }}</div></div>
                        <div><div class="text-ink-500">{{ ($s['kena_pph'] ?? false) ? 'PPh 23 (2%)' : 'Tanpa PPh' }}</div><div class="font-medium tabular-nums text-amber-700">{{ ($s['pph'] ?? 0) > 0 ? '− ' . fmtIDR($s['pph']) : '—' }}</div></div>
                        <div><div class="text-ink-500">{{ ($s['pph'] ?? 0) > 0 ? 'Kas Diterima' : 'Total Tagihan' }}</div><div class="font-semibold tabular-nums text-ink-900">{{ fmtIDR($o['total']) }}</div></div>
                    </div>
                    @if ($pays->count() > 0)
                        <div class="mt-3">
                            <div class="text-[11px] text-ink-500 mb-2 uppercase tracking-wider">Riwayat Pembayaran</div>
                            @foreach ($pays as $p)
                                <div class="flex items-center justify-between text-[12px] py-1">
                                    <div>
                                        <span class="mono text-[11.5px] mr-2">{{ $p['no_kuitansi'] }}</span>
                                        <span class="text-ink-500">{{ fmtDate($p['tgl_bayar']) }} · <span class="capitalize">{{ $p['metode_bayar'] }}</span></span>
                                    </div>
                                    <div class="tabular-nums text-emerald-700 font-medium">+{{ fmtIDR($p['nominal_bayar']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if ($o['sisa'] > 0)
                        <div class="mt-3 pt-3 border-t border-ink-100 flex items-center justify-between">
                            <div class="text-[12px] text-ink-500">Sisa Tagihan</div>
                            <div class="tabular-nums font-semibold text-red-700">{{ fmtIDR($o['sisa']) }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                    <span>Serah-Terima (pengambilan &amp; pengembalian)</span>
                    <a href="{{ route('handover') }}" class="btn btn-ghost text-[11px]">Buka modul <x-icon name="chevronR" :size="11" /></a>
                </div>
                @if ($handovers->count() === 0)
                    <div class="card p-4 text-center text-[12px] text-ink-400">Belum ada catatan serah-terima</div>
                @endif
                @foreach ($handovers as $h)
                    <div class="card p-3 mb-2">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <x-status-pill :status="$h['jenis_aktivitas']" />
                                <span class="text-[12px] text-ink-500">{{ fmtDateTime($h['tanggal']) }}</span>
                            </div>
                            <span class="text-[11px] text-ink-400">PIC: {{ $h['pic_dari_rental'] }}</span>
                        </div>
                        <div class="text-[11.5px] text-ink-600">{{ $h['kondisi_genset'] }}</div>
                    </div>
                @endforeach
            </div>

            <x-slot:footer>
                <button class="btn btn-ghost" @click="open = null">Tutup</button>
                <button class="btn btn-ghost" @click="window.print()"><x-icon name="doc" :size="14" /> Cetak Invoice</button>
                @if ($o['sisa'] > 0 && $s['status_pesanan'] !== 'dibatalkan')
                    <button class="btn btn-primary" :disabled="saving"
                        @click="bayar({{ $s['id_sewa'] }}, {{ $o['sisa'] }})">Catat Pembayaran</button>
                @endif
            </x-slot:footer>
        </x-drawer>
    @endforeach

    {{-- ===== Create wizard ===== --}}
    <x-drawer show="createOpen" close="createOpen = false" :width="640"
        title="Penyewaan Baru">
        <x-slot:subtitle>
            <span x-text="`Langkah ${step}/3 · Pilih unit & tanggal → Rincian biaya → Konfirmasi`"></span>
        </x-slot:subtitle>

        <div class="flex items-center gap-2 mb-5">
            @foreach (['Pelanggan & Unit', 'Rincian Biaya', 'Konfirmasi'] as $i => $lbl)
                <div class="flex items-center gap-2 text-[12px]" :class="step >= {{ $i + 1 }} ? 'text-brand-600 font-medium' : 'text-ink-400'">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold"
                         :class="step > {{ $i + 1 }} ? 'bg-brand-500 text-white' : (step === {{ $i + 1 }} ? 'bg-brand-100 text-brand-600 border border-brand-300' : 'bg-ink-100 text-ink-400')">
                        <span x-text="step > {{ $i + 1 }} ? '✓' : '{{ $i + 1 }}'"></span>
                    </div>
                    {{ $lbl }}
                </div>
                @if ($i < 2)<div class="h-px flex-1 bg-ink-200"></div>@endif
            @endforeach
        </div>

        {{-- Step 1 --}}
        <div x-show="step === 1" class="space-y-4">
            <x-form-field label="Pelanggan" :required="true">
                <select class="inp" x-model="form.id_pelanggan">
                    <option value="">— Pilih pelanggan —</option>
                    @foreach ($d['pelanggan'] as $p)
                        <option value="{{ $p['id_pelanggan'] }}">{{ $p['nama_perusahaan'] }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Tgl. Mulai" :required="true">
                    <input type="date" class="inp" x-model="form.start_date" />
                </x-form-field>
                <x-form-field label="Tgl. Selesai" :required="true">
                    <input type="date" class="inp" x-model="form.end_date" />
                </x-form-field>
            </div>
            <x-form-field label="Unit Genset" :required="true"
                hint="{{ $availableGensets->count() }} unit tersedia di gudang. Unit yang dipilih otomatis ditandai 'disewa' pada rentang tanggal di atas.">
                <div class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto">
                    @foreach ($availableGensets as $g)
                        @php
                            $k = $kategoriById[$g['id_kategori']] ?? $kategoriById[''];
                            $defaultPrice = $k['id_kategori'] === 1 ? 1500000 : ($k['id_kategori'] === 2 ? 2500000 : ($k['id_kategori'] === 3 ? 4800000 : 7500000));
                        @endphp
                        <div @click="form.id_genset = {{ $g['id_genset'] }}; form.harga_sewa_unit = {{ $defaultPrice }}"
                             class="p-3 rounded border cursor-pointer flex items-center gap-3"
                             :class="form.id_genset === {{ $g['id_genset'] }} ? 'border-brand-500 bg-brand-50' : 'border-ink-200 hover:border-ink-300'">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                 :class="form.id_genset === {{ $g['id_genset'] }} ? 'border-brand-500 bg-brand-500' : 'border-ink-300'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="form.id_genset === {{ $g['id_genset'] }}"></div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-[13px]">{{ ($merekById[$g['id_merek']]['nama_merek'] ?? '—') }} {{ $k['kapasitas'] }}</div>
                                <div class="mono text-[11px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                            </div>
                            <x-status-pill status="tersedia" />
                        </div>
                    @endforeach
                </div>
            </x-form-field>
        </div>

        {{-- Step 2 --}}
        <div x-show="step === 2" class="space-y-4">
            <x-form-field label="Alamat Proyek" :required="true">
                <input class="inp" x-model="form.alamat_proyek" placeholder="Lokasi pemasangan unit (juga jadi lokasi_terkini saat handover pengambilan)" />
            </x-form-field>

            {{-- Satuan sewa: harian / bulanan --}}
            <x-form-field label="Satuan Sewa" :required="true" hint="Harga sewa dihitung per hari atau per bulan (1 bulan = 30 hari).">
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['harian' => ['Harian', 'Tarif per hari'], 'bulanan' => ['Bulanan', 'Tarif per bulan (30 hari)']] as $sat => $info)
                        <button type="button" @click="form.satuan_sewa = '{{ $sat }}'"
                            class="p-2.5 rounded border text-left"
                            :class="form.satuan_sewa === '{{ $sat }}' ? 'border-brand-500 bg-brand-50' : 'border-ink-200'">
                            <div class="font-medium text-[13px]">{{ $info[0] }}</div>
                            <div class="text-[11px] text-ink-500">{{ $info[1] }}</div>
                        </button>
                    @endforeach
                </div>
            </x-form-field>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Harga Sewa Pokok" :required="true" hint="Per satuan terpilih, di luar BBM & operator.">
                    <div class="relative">
                        <input type="number" class="inp pr-12" x-model="form.harga_sewa_unit" />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-ink-400" x-text="'/ ' + satuanLabel"></span>
                    </div>
                </x-form-field>
                <x-form-field label="Biaya Operator"><input type="number" class="inp" x-model="form.biaya_operator" /></x-form-field>
                <x-form-field label="Biaya Mob-Demob"><input type="number" class="inp" x-model="form.biaya_mobdemob" /></x-form-field>
                <x-form-field label="Biaya BBM" hint="Opsional — kosongkan bila pelanggan mengisi BBM sendiri."><input type="number" class="inp" x-model="form.biaya_bbm" /></x-form-field>
            </div>

            {{-- Toggle PPN & PPh --}}
            <div class="grid grid-cols-2 gap-2">
                <label class="card p-3 flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" x-model="form.kena_ppn" class="w-4 h-4" />
                    <div>
                        <div class="text-[12.5px] font-medium">Kena PPN 11%</div>
                        <div class="text-[10.5px] text-ink-500">PPN keluaran</div>
                    </div>
                </label>
                <label class="card p-3 flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" x-model="form.kena_pph" class="w-4 h-4" />
                    <div>
                        <div class="text-[12.5px] font-medium">Potong PPh 23 (2%)</div>
                        <div class="text-[10.5px] text-ink-500">Dipotong pelanggan</div>
                    </div>
                </label>
            </div>

            <div class="card p-4 bg-ink-50 border-ink-200">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500 mb-2">Ringkasan</div>
                <div class="space-y-1.5 text-[12.5px]">
                    <div class="flex justify-between"><span x-text="`Sewa ${qty} ${satuanLabel} × ${fmt(form.harga_sewa_unit)}`"></span><span class="mono" x-text="fmt(sewaPokok)"></span></div>
                    <div class="flex justify-between text-ink-600"><span>Operator + Mob-Demob + BBM</span><span class="mono" x-text="fmt(Number(form.biaya_operator||0)+Number(form.biaya_mobdemob||0)+Number(form.biaya_bbm||0))"></span></div>
                    <div class="flex justify-between text-ink-600 pt-1 border-t border-ink-200"><span>Subtotal (DPP)</span><span class="mono" x-text="fmt(sub)"></span></div>
                    <div class="flex justify-between text-ink-600" x-show="form.kena_ppn"><span>PPN 11%</span><span class="mono" x-text="`+ ${fmt(ppn)}`"></span></div>
                    <div class="flex justify-between text-amber-700" x-show="form.kena_pph"><span>PPh 23 (2%) dipotong</span><span class="mono" x-text="`− ${fmt(pph)}`"></span></div>
                    <div class="flex justify-between font-semibold text-ink-900 pt-1 border-t border-ink-200"><span x-text="form.kena_pph ? 'Kas diterima' : 'Total'"></span><span class="mono" x-text="fmt(totalTagihan)"></span></div>
                </div>
            </div>
        </div>

        {{-- Step 3 --}}
        <div x-show="step === 3" class="space-y-4">
            <div class="card p-4">
                <div class="text-[11px] text-ink-500 uppercase tracking-wider font-semibold mb-3">Akan Dibuat (otomatis)</div>
                <div class="space-y-2 text-[12.5px]">
                    <div class="flex items-center gap-2"><x-icon name="check" :size="14" class="text-emerald-600" /> Transaksi sewa baru (status: deal) sekaligus invoice-nya</div>
                    <div class="flex items-center gap-2"><x-icon name="check" :size="14" class="text-emerald-600" /> Detail unit yang disewa beserta tanggal mulai &amp; selesai</div>
                    <div class="flex items-center gap-2"><x-icon name="check" :size="14" class="text-emerald-600" /> Nomor invoice otomatis (mis. <span class="mono">INV/2026/04/006</span>)</div>
                    <div class="flex items-center gap-2"><x-icon name="check" :size="14" class="text-emerald-600" /> Kalender unit untuk rentang sewa otomatis ditandai "disewa"</div>
                    <div class="flex items-center gap-2"><x-icon name="check" :size="14" class="text-emerald-600" /> Jurnal akuntansi (Pendapatan Sewa &amp; Piutang) dibuat otomatis</div>
                </div>
            </div>
            <div class="card p-4">
                <div class="text-[11px] text-ink-500 uppercase tracking-wider font-semibold mb-3">Preview Jurnal Sewa</div>
                <table class="w-full text-[11.5px] mono">
                    <thead class="text-ink-500"><tr><th class="text-left py-1">Akun</th><th class="text-right py-1">Debit</th><th class="text-right py-1">Kredit</th></tr></thead>
                    <tbody>
                        <tr class="border-t border-ink-100"><td class="py-1.5">1-1101 Piutang Usaha</td><td class="text-right tabular-nums" x-text="fmt(totalTagihan)"></td><td class="text-right">—</td></tr>
                        <tr class="border-t border-ink-100" x-show="form.kena_pph"><td class="py-1.5">1-1102 PPh 23 Dibayar Dimuka</td><td class="text-right tabular-nums" x-text="fmt(pph)"></td><td class="text-right">—</td></tr>
                        <tr class="border-t border-ink-100"><td class="py-1.5 pl-4">4-1001 Pendapatan Sewa Genset</td><td class="text-right">—</td><td class="text-right tabular-nums" x-text="fmt(sewaPokok)"></td></tr>
                        <tr class="border-t border-ink-100" x-show="(Number(form.biaya_operator||0)+Number(form.biaya_mobdemob||0)+Number(form.biaya_bbm||0)) > 0"><td class="py-1.5 pl-4">4-1002 Pendapatan Operator &amp; BBM</td><td class="text-right">—</td><td class="text-right tabular-nums" x-text="fmt(Number(form.biaya_operator||0)+Number(form.biaya_mobdemob||0)+Number(form.biaya_bbm||0))"></td></tr>
                        <tr class="border-t border-ink-100" x-show="form.kena_ppn"><td class="py-1.5 pl-4">2-2001 PPN Keluaran</td><td class="text-right">—</td><td class="text-right tabular-nums" x-text="fmt(ppn)"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
            <button class="btn btn-ghost" x-show="step > 1" @click="step--">Kembali</button>
            <button class="btn btn-primary" x-show="step < 3" @click="step++">Lanjut <x-icon name="chevronR" :size="12" /></button>
            <button class="btn btn-primary" x-show="step === 3" :disabled="saving" @click="simpanKontrak()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Buat Kontrak + Invoice'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
