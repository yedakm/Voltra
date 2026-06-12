@extends('layouts.app')

@section('content')
@php
    $jurnal = collect($d['jurnal_akuntansi']);
    $detailJurnal = collect($d['detail_jurnal']);
    $akun = collect($d['akun_perkiraan']);
    $akunByKode = $d['akunByKode'];
    $periodeById = $d['periodeById'];
    $jurnalById = $d['jurnalById'];
    $gensetById = $d['gensetById'];
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];
    $months = voltra_month_names();

    $jenisLabels = [
        'pembelian_aset' => ['Pembelian Aset', 'blue'],
        'sewa' => ['Sewa', 'brand'],
        'pembayaran' => ['Pembayaran', 'green'],
        'pemeliharaan' => ['Pemeliharaan', 'amber'],
        'beban_operasional' => ['Beban Operasional', 'red'],
        'penyusutan' => ['Penyusutan', 'gray'],
        'penjualan_aset' => ['Penjualan Aset', 'purple'],
        'penyesuaian' => ['Penyesuaian', 'amber'],
        'penutup' => ['Penutup', 'gray'],
        'manual' => ['Manual', 'gray'],
        'koreksi' => ['Koreksi', 'red'],
    ];

    $totalDebit = $detailJurnal->sum('debit');
    $totalKredit = $detailJurnal->sum('kredit');

    // Id jurnal asal yang sudah punya jurnal koreksi (untuk badge "sudah dikoreksi").
    $sudahDikoreksi = $jurnal->where('jenis_jurnal', 'koreksi')->pluck('referensi_id')->filter()->unique()->all();

    $tabs = [
        ['id' => 'jurnal', 'label' => 'Jurnal Akuntansi', 'count' => $jurnal->count()],
        ['id' => 'akun', 'label' => 'Bagan Akun (COA)', 'count' => $akun->count()],
        ['id' => 'penyusutan', 'label' => 'Jadwal Penyusutan', 'count' => count($d['jadwal_penyusutan'])],
    ];

    $headers = $akun->whereNull('kode_parent');
    $katColor = ['aset' => '#177f8a', 'kewajiban' => '#a6700f', 'ekuitas' => '#5b2ea8', 'pendapatan' => '#1f6a34', 'beban' => '#b42318'];
@endphp

<div x-data="{
    tab: 'jurnal', filterJenis: 'all', saving: false, manualOpen: false, akunOpen: false,
    fmt: window.fmtIDR,
    mj: {
        tanggal: '{{ date('Y-m-d') }}', keterangan: '',
        lines: [ { kode_akun: '1-1001', debit: 0, kredit: 0 }, { kode_akun: '3-1001', debit: 0, kredit: 0 } ],
    },
    akunBaru: { kode_akun: '', nama_akun: '', kategori_akun: 'aset', sub_kategori: '', saldo_normal: 'debit', kode_parent: '' },
    addLine() { this.mj.lines.push({ kode_akun: '', debit: 0, kredit: 0 }); },
    removeLine(i) { if (this.mj.lines.length > 2) this.mj.lines.splice(i, 1); },
    get totalDebit() { return this.mj.lines.reduce((s, l) => s + Number(l.debit || 0), 0); },
    get totalKredit() { return this.mj.lines.reduce((s, l) => s + Number(l.kredit || 0), 0); },
    get balanced() { return this.totalDebit > 0 && this.totalDebit === this.totalKredit; },
    jalankanDepresiasi() {
        if (this.saving) return;
        this.saving = true;
        window.voltraSave('/aksi/depreciation/run', { periode: '{{ date('Y-m') }}' },
            r => r.skipped ? 'Periode ini sudah dihitung sebelumnya.'
                : 'Penyusutan ' + r.unit + ' unit berhasil dicatat.').catch(() => this.saving = false);
    },
    simpanJurnal() {
        if (!this.balanced) { this.$store.toasts.push('Total debit harus sama dengan total kredit (dan > 0).','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/journal/manual', this.mj,
            r => 'Jurnal ' + r.jurnal.no_bukti + ' tersimpan.').catch(() => this.saving = false);
    },
    simpanAkun() {
        if (!this.akunBaru.kode_akun || !this.akunBaru.nama_akun) {
            this.$store.toasts.push('Kode & nama akun wajib diisi.','error'); return;
        }
        this.saving = true;
        window.voltraSave('/aksi/master/akun-perkiraan', this.akunBaru,
            r => 'Akun ' + r.data.kode_akun + ' ' + r.data.nama_akun + ' tersimpan.').catch(() => this.saving = false);
    },
    editJurnalOpen: false, ejId: null, ejNoBukti: '',
    ej: { keterangan: '', lines: [] },
    mulaiEditJurnal(j) {
        this.ejId = j.id;
        this.ejNoBukti = j.no_bukti;
        this.ej = { keterangan: j.keterangan || '', lines: JSON.parse(JSON.stringify(j.lines)) };
        while (this.ej.lines.length < 2) this.ej.lines.push({ kode_akun: '', debit: 0, kredit: 0, keterangan: '' });
        this.editJurnalOpen = true;
    },
    addLineEdit() { this.ej.lines.push({ kode_akun: '', debit: 0, kredit: 0, keterangan: '' }); },
    removeLineEdit(i) { if (this.ej.lines.length > 2) this.ej.lines.splice(i, 1); },
    get ejDebit() { return this.ej.lines.reduce((s, l) => s + Number(l.debit || 0), 0); },
    get ejKredit() { return this.ej.lines.reduce((s, l) => s + Number(l.kredit || 0), 0); },
    get ejBalanced() { return this.ejDebit > 0 && this.ejDebit === this.ejKredit; },
    simpanEditJurnal() {
        if (!this.ejBalanced) { this.$store.toasts.push('Total debit harus sama dengan kredit (dan > 0).','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/journal/' + this.ejId + '/update', this.ej,
            r => 'Jurnal ' + (r.jurnal ? r.jurnal.no_bukti : '') + ' diperbarui.').catch(() => this.saving = false);
    },
    koreksiOpen: false, korAsalLines: [],
    kor: { referensi_id: null, no_bukti_asal: '', tanggal: '{{ date('Y-m-d') }}', keterangan: '', lines: [] },
    mulaiKoreksi(j) {
        this.kor = {
            referensi_id: j.id, no_bukti_asal: j.no_bukti, tanggal: '{{ date('Y-m-d') }}',
            keterangan: 'Koreksi atas ' + j.no_bukti,
            lines: [ { kode_akun: '', debit: 0, kredit: 0, keterangan: '' }, { kode_akun: '', debit: 0, kredit: 0, keterangan: '' } ],
        };
        this.korAsalLines = JSON.parse(JSON.stringify(j.lines));
        this.koreksiOpen = true;
    },
    preIsiPembalik() {
        // Balik debit <-> kredit dari jurnal asal (membatalkan efeknya).
        this.kor.lines = this.korAsalLines.map(l => ({ kode_akun: l.kode_akun, debit: Number(l.kredit) || 0, kredit: Number(l.debit) || 0, keterangan: 'Pembalik' }));
    },
    addLineKor() { this.kor.lines.push({ kode_akun: '', debit: 0, kredit: 0, keterangan: '' }); },
    removeLineKor(i) { if (this.kor.lines.length > 2) this.kor.lines.splice(i, 1); },
    get korDebit() { return this.kor.lines.reduce((s, l) => s + Number(l.debit || 0), 0); },
    get korKredit() { return this.kor.lines.reduce((s, l) => s + Number(l.kredit || 0), 0); },
    get korBalanced() { return this.korDebit > 0 && this.korDebit === this.korKredit; },
    simpanKoreksi() {
        if (!this.korBalanced) { this.$store.toasts.push('Total debit harus sama dengan kredit (dan > 0).','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/journal/koreksi', this.kor,
            r => 'Jurnal koreksi ' + (r.jurnal ? r.jurnal.no_bukti : '') + ' dibuat.').catch(() => this.saving = false);
    },
}">
    <x-section-header title="Jurnal & Bagan Akun"
        subtitle="Catatan jurnal akuntansi & daftar akun (Bagan Akun)">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
            <button class="btn btn-ghost" @click="akunOpen = true"><x-icon name="ledger" :size="14" /> Akun Baru</button>
            <button class="btn btn-primary" @click="manualOpen = true">
                <x-icon name="plus" :size="14" /> Jurnal Manual</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-4 gap-3 mb-5">
        <x-stat-card label="Total Jurnal (Apr)" :value="$jurnal->count()" sub="periode aktif" tone="default" icon="ledger" />
        <x-stat-card label="Total Debit" :value="fmtIDR($totalDebit)" tone="default" />
        <x-stat-card label="Total Kredit" :value="fmtIDR($totalKredit)" tone="default" />
        <x-stat-card label="Selisih" :value="fmtIDR(abs($totalDebit - $totalKredit))" sub="seimbang ✓" tone="ok" />
    </div>

    <x-tab-bar :tabs="$tabs" />

    {{-- ===== Tab: Jurnal ===== --}}
    <div x-show="tab === 'jurnal'">
        <x-toolbar placeholder="Cari no. bukti, keterangan...">
            <x-slot:filters>
                <select class="inp max-w-xs" x-model="filterJenis">
                    <option value="all">Semua jenis jurnal</option>
                    @foreach ($jenisLabels as $k => $v)
                        <option value="{{ $k }}">{{ $v[0] }}</option>
                    @endforeach
                </select>
            </x-slot:filters>
        </x-toolbar>
        <div class="space-y-3">
            @foreach ($jurnal as $j)
                @php
                    $lines = $detailJurnal->where('id_jurnal', $j['id_jurnal']);
                    $periode = $periodeById[$j['id_periode']] ?? null;
                    $jl = $jenisLabels[$j['jenis_jurnal']] ?? [$j['jenis_jurnal'], 'gray'];
                    $periodeAktifJurnal = ($periode['status'] ?? '') === 'aktif';
                    $jForEdit = [
                        'id' => $j['id_jurnal'],
                        'no_bukti' => $j['no_bukti'],
                        'keterangan' => $j['keterangan'],
                        'lines' => $lines->map(fn ($l) => [
                            'kode_akun' => $l['kode_akun'],
                            'debit' => (float) $l['debit'],
                            'kredit' => (float) $l['kredit'],
                            'keterangan' => $l['keterangan'] ?? '',
                        ])->values()->all(),
                    ];
                @endphp
                <div class="card overflow-hidden" x-show="filterJenis === 'all' || filterJenis === '{{ $j['jenis_jurnal'] }}'">
                    <div class="px-4 py-2.5 bg-ink-50 border-b border-ink-100 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="mono text-[12.5px] font-semibold">{{ $j['no_bukti'] }}</span>
                                <x-status-pill :status="$j['jenis_jurnal']" :tone="$jl[1]" :label="$jl[0]" />
                                <x-status-pill status="posted" />
                                @if (in_array($j['id_jurnal'], $sudahDikoreksi))
                                    <span class="pill" style="background:#fef3f2;color:#b42318"><span class="dot" style="background:#e56362"></span>sudah dikoreksi</span>
                                @endif
                            </div>
                            <div class="text-[11.5px] text-ink-500 mt-0.5">
                                {{ fmtDate($j['tanggal']) }} · {{ $j['keterangan'] }}
                                @if ($j['referensi_tipe'])
                                    <span class="ml-2 mono text-[10.5px] bg-white px-1.5 py-0.5 rounded border border-ink-200">ref: {{ $j['referensi_tipe'] }}#{{ $j['referensi_id'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="text-[12px] text-ink-600">Total: <span class="mono font-semibold">{{ fmtIDR($j['total_debit']) }}</span></div>
                                <div class="text-[10.5px] text-ink-400">Periode {{ $months[$periode['bulan'] - 1] }} {{ $periode['tahun'] }}</div>
                            </div>
                            @if ($periodeAktifJurnal)
                                <button class="btn btn-ghost text-[12px]" @click="mulaiEditJurnal(@js($jForEdit))">
                                    <x-icon name="edit" :size="13" /> Edit
                                </button>
                            @else
                                <span class="text-[10.5px] text-ink-400 flex items-center gap-1" title="Periode sudah ditutup. Ubah lewat jurnal koreksi.">
                                    <x-icon name="check" :size="11" /> Terkunci
                                </span>
                            @endif
                            @if ($j['jenis_jurnal'] !== 'koreksi')
                                <button class="btn btn-ghost text-[12px]" title="Buat jurnal koreksi" @click="mulaiKoreksi(@js($jForEdit))">
                                    <x-icon name="arrow" :size="13" /> Koreksi
                                </button>
                            @endif
                        </div>
                    </div>
                    <table class="w-full text-[12.5px]">
                        <tbody>
                            @foreach ($lines as $l)
                                @php $a = $akunByKode[$l['kode_akun']] ?? null; $indent = $l['kredit'] > 0; @endphp
                                <tr class="border-t border-ink-100">
                                    <td class="px-4 py-1.5 {{ $indent ? 'pl-10' : '' }}">
                                        <span class="mono text-[11px] text-ink-500 mr-2">{{ $l['kode_akun'] }}</span>
                                        <span class="{{ $indent ? 'text-ink-600' : 'font-medium' }}">{{ $a['nama_akun'] ?? '' }}</span>
                                        @if ($l['keterangan'])<span class="text-[11px] text-ink-400 ml-2">— {{ $l['keterangan'] }}</span>@endif
                                    </td>
                                    <td class="px-4 py-1.5 text-right mono tabular-nums w-40">@if ($l['debit'] > 0){{ fmtIDR($l['debit']) }}@else<span class="text-ink-300">—</span>@endif</td>
                                    <td class="px-4 py-1.5 text-right mono tabular-nums w-40">@if ($l['kredit'] > 0){{ fmtIDR($l['kredit']) }}@else<span class="text-ink-300">—</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Tab: COA ===== --}}
    <div x-show="tab === 'akun'" x-cloak class="space-y-4 mt-3">
        @foreach ($headers as $h)
            @php
                $children = $akun->where('kode_parent', $h['kode_akun']);
                $childCodes = $children->pluck('kode_akun')->all();
                $rows = $akun->filter(fn ($a) => $a['kode_parent'] && ($a['kode_parent'] === $h['kode_akun'] || in_array($a['kode_parent'], $childCodes, true)));
                $color = $katColor[$h['kategori_akun']];
            @endphp
            <div class="card overflow-hidden">
                <div class="px-4 py-2.5 border-b border-ink-100 flex items-center gap-2" style="background:{{ $color }}10">
                    <span class="mono text-[11px] font-semibold" style="color:{{ $color }}">{{ $h['kode_akun'] }}</span>
                    <span class="font-semibold text-[13px]" style="color:{{ $color }}">{{ $h['nama_akun'] }}</span>
                    <span class="text-[10px] uppercase tracking-wider text-ink-500 ml-2">saldo normal: {{ $h['saldo_normal'] }}</span>
                </div>
                <table class="w-full text-[12.5px]">
                    <tbody>
                        @foreach ($rows as $a)
                            @php $isSub = in_array($a['kode_akun'], $childCodes, true); @endphp
                            <tr class="border-b border-ink-100 hoverable">
                                <td class="px-4 py-2 mono w-32 {{ $isSub ? 'font-semibold' : '' }}" style="padding-left:{{ $isSub ? '1rem' : '2rem' }}">{{ $a['kode_akun'] }}</td>
                                <td class="px-4 py-2 {{ $isSub ? 'font-semibold text-ink-800' : 'text-ink-700' }}">{{ $a['nama_akun'] }}</td>
                                <td class="px-4 py-2 text-[11px] text-ink-500 italic">{{ $a['sub_kategori'] }}</td>
                                <td class="px-4 py-2 text-right text-[11px] text-ink-500 capitalize">{{ $a['saldo_normal'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- ===== Drawer: Jurnal Manual ===== --}}
    <x-drawer show="manualOpen" close="manualOpen = false" :width="720"
        title="Jurnal Manual"
        subtitle="Catat transaksi non-otomatis · contoh: setoran modal awal (Kas / Modal)">

        <div class="grid grid-cols-2 gap-3 mb-4">
            <x-form-field label="Tanggal" :required="true">
                <input type="date" class="inp" x-model="mj.tanggal" />
            </x-form-field>
            <x-form-field label="Keterangan">
                <input class="inp" x-model="mj.keterangan" placeholder="Mis. Setoran modal pendiri" />
            </x-form-field>
        </div>

        <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Detail Baris Jurnal</div>

        @if (count($d['akun_perkiraan']) === 0)
            <div class="card p-3 bg-amber-50 border-amber-200 mb-3 text-[12.5px] text-amber-900">
                <div class="font-semibold">Bagan Akun masih kosong</div>
                <div>Perusahaan ini belum punya daftar akun. Minimal harus ada akun Kas dan Modal sebelum bisa input jurnal.</div>
            </div>
        @endif

        <div class="card overflow-hidden">
            <table class="w-full text-[12.5px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2 text-left font-semibold">Akun</th>
                        <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                        <th class="px-3 py-2 text-right font-semibold w-32">Debit</th>
                        <th class="px-3 py-2 text-right font-semibold w-32">Kredit</th>
                        <th class="px-3 py-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, i) in mj.lines" :key="i">
                        <tr class="border-b border-ink-100">
                            <td class="px-2 py-1.5">
                                <select class="inp text-[12px]" x-model="line.kode_akun">
                                    <option value="">— Pilih —</option>
                                    @foreach ($akun->whereNotNull('kode_parent') as $a)
                                        <option value="{{ $a['kode_akun'] }}">{{ $a['kode_akun'] }} · {{ $a['nama_akun'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-1.5">
                                <input class="inp text-[12px]" x-model="line.keterangan" placeholder="—" />
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="number" min="0" step="any" class="inp text-[12px] text-right mono" x-model.number="line.debit" />
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="number" min="0" step="any" class="inp text-[12px] text-right mono" x-model.number="line.kredit" />
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" class="text-ink-400 hover:text-red-600" @click="removeLine(i)" :disabled="mj.lines.length <= 2">
                                    <x-icon name="trash" :size="14" />
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-ink-50">
                    <tr class="border-t border-ink-200 text-[12px]">
                        <td colspan="2" class="px-3 py-2 text-right text-ink-500">Total</td>
                        <td class="px-3 py-2 text-right mono font-semibold" x-text="fmt(totalDebit)"></td>
                        <td class="px-3 py-2 text-right mono font-semibold" x-text="fmt(totalKredit)"></td>
                        <td></td>
                    </tr>
                    <tr class="border-t border-ink-200 text-[11.5px]">
                        <td colspan="2" class="px-3 py-2 text-right text-ink-500">Selisih</td>
                        <td colspan="2" class="px-3 py-2 text-right mono"
                            :class="balanced ? 'text-emerald-700 font-semibold' : 'text-red-600'"
                            x-text="balanced ? 'Seimbang ✓' : fmt(Math.abs(totalDebit - totalKredit))"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-ghost mt-3 text-[12px]" @click="addLine()">
            <x-icon name="plus" :size="12" /> Tambah Baris
        </button>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="manualOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving || !balanced" @click="simpanJurnal()">
                <x-icon name="check" :size="14" />
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Jurnal'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>

    {{-- ===== Drawer: Edit Jurnal ===== --}}
    <x-drawer show="editJurnalOpen" close="editJurnalOpen = false" :width="720"
        title="Edit Jurnal" subtitle="Ubah baris jurnal. Hanya untuk periode yang masih aktif.">

        <div class="card p-3 bg-amber-50 border-amber-200 mb-4 text-[12px] text-amber-900">
            Mengedit <span class="mono font-semibold" x-text="ejNoBukti"></span>. Untuk jurnal otomatis (sewa, pembayaran, dst.) perubahan di sini <b>tidak</b> ikut mengubah transaksi sumbernya.
        </div>

        <x-form-field label="Keterangan">
            <input class="inp" x-model="ej.keterangan" />
        </x-form-field>

        <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2 mt-4">Detail Baris Jurnal</div>
        <div class="card overflow-hidden">
            <table class="w-full text-[12.5px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2 text-left font-semibold">Akun</th>
                        <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                        <th class="px-3 py-2 text-right font-semibold w-32">Debit</th>
                        <th class="px-3 py-2 text-right font-semibold w-32">Kredit</th>
                        <th class="px-3 py-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, i) in ej.lines" :key="i">
                        <tr class="border-b border-ink-100">
                            <td class="px-2 py-1.5">
                                <select class="inp text-[12px]" x-model="line.kode_akun">
                                    <option value="">— Pilih —</option>
                                    @foreach ($akun->whereNotNull('kode_parent') as $a)
                                        <option value="{{ $a['kode_akun'] }}">{{ $a['kode_akun'] }} · {{ $a['nama_akun'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-1.5"><input class="inp text-[12px]" x-model="line.keterangan" placeholder="—" /></td>
                            <td class="px-2 py-1.5"><input type="number" min="0" step="any" class="inp text-[12px] text-right mono" x-model.number="line.debit" /></td>
                            <td class="px-2 py-1.5"><input type="number" min="0" step="any" class="inp text-[12px] text-right mono" x-model.number="line.kredit" /></td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" class="text-ink-400 hover:text-red-600" @click="removeLineEdit(i)" :disabled="ej.lines.length <= 2">
                                    <x-icon name="trash" :size="14" />
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-ink-50">
                    <tr class="border-t border-ink-200 text-[12px]">
                        <td colspan="2" class="px-3 py-2 text-right text-ink-500">Total</td>
                        <td class="px-3 py-2 text-right mono font-semibold" x-text="fmt(ejDebit)"></td>
                        <td class="px-3 py-2 text-right mono font-semibold" x-text="fmt(ejKredit)"></td>
                        <td></td>
                    </tr>
                    <tr class="border-t border-ink-200 text-[11.5px]">
                        <td colspan="2" class="px-3 py-2 text-right text-ink-500">Selisih</td>
                        <td colspan="2" class="px-3 py-2 text-right mono" :class="ejBalanced ? 'text-emerald-700 font-semibold' : 'text-red-600'" x-text="ejBalanced ? 'Seimbang ✓' : fmt(Math.abs(ejDebit - ejKredit))"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="btn btn-ghost mt-3 text-[12px]" @click="addLineEdit()"><x-icon name="plus" :size="12" /> Tambah Baris</button>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="editJurnalOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving || !ejBalanced" @click="simpanEditJurnal()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>

    {{-- ===== Drawer: Jurnal Koreksi ===== --}}
    <x-drawer show="koreksiOpen" close="koreksiOpen = false" :width="760" title="Jurnal Koreksi">
        <x-slot:subtitle>Koreksi atas <span class="mono font-semibold" x-text="kor.no_bukti_asal"></span>. Jurnal asal tidak diubah, jejak audit tetap utuh.</x-slot:subtitle>

        <div class="card p-3 bg-amber-50 border-amber-200 mb-4 text-[12px] text-amber-900">
            Koreksi dicatat sebagai <b>jurnal baru</b> (jenis Koreksi) di periode aktif sesuai tanggal di bawah. Pakai <b>Pre-isi pembalik</b> untuk membatalkan jurnal asal, lalu tambahkan baris yang benar bila perlu. (Periode tertutup akan ditolak; pilih tanggal di periode yang masih aktif.)
        </div>

        {{-- Referensi jurnal asal --}}
        <div class="card overflow-hidden mb-4">
            <div class="px-3 py-2 bg-ink-50 border-b border-ink-100 flex items-center justify-between">
                <span class="text-[11px] uppercase tracking-wider font-semibold text-ink-500">Jurnal asal (referensi)</span>
                <button type="button" class="btn btn-ghost text-[11px]" @click="preIsiPembalik()"><x-icon name="arrow" :size="12" /> Pre-isi pembalik</button>
            </div>
            <table class="w-full text-[11.5px] mono">
                <tbody>
                    <template x-for="(l, i) in korAsalLines" :key="i">
                        <tr class="border-t border-ink-100">
                            <td class="px-3 py-1" x-text="l.kode_akun"></td>
                            <td class="px-3 py-1 text-right" x-text="Number(l.debit) ? fmt(l.debit) : '—'"></td>
                            <td class="px-3 py-1 text-right" x-text="Number(l.kredit) ? fmt(l.kredit) : '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <x-form-field label="Tanggal Koreksi" :required="true">
                <input type="date" class="inp" x-model="kor.tanggal" />
            </x-form-field>
            <x-form-field label="Keterangan">
                <input class="inp" x-model="kor.keterangan" />
            </x-form-field>
        </div>

        <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Baris Jurnal Koreksi</div>
        <div class="card overflow-hidden">
            <table class="w-full text-[12.5px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2 text-left font-semibold">Akun</th>
                        <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                        <th class="px-3 py-2 text-right font-semibold w-32">Debit</th>
                        <th class="px-3 py-2 text-right font-semibold w-32">Kredit</th>
                        <th class="px-3 py-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, i) in kor.lines" :key="i">
                        <tr class="border-b border-ink-100">
                            <td class="px-2 py-1.5">
                                <select class="inp text-[12px]" x-model="line.kode_akun">
                                    <option value="">— Pilih —</option>
                                    @foreach ($akun->whereNotNull('kode_parent') as $a)
                                        <option value="{{ $a['kode_akun'] }}">{{ $a['kode_akun'] }} · {{ $a['nama_akun'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-1.5"><input class="inp text-[12px]" x-model="line.keterangan" placeholder="—" /></td>
                            <td class="px-2 py-1.5"><input type="number" min="0" step="any" class="inp text-[12px] text-right mono" x-model.number="line.debit" /></td>
                            <td class="px-2 py-1.5"><input type="number" min="0" step="any" class="inp text-[12px] text-right mono" x-model.number="line.kredit" /></td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" class="text-ink-400 hover:text-red-600" @click="removeLineKor(i)" :disabled="kor.lines.length <= 2">
                                    <x-icon name="trash" :size="14" />
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-ink-50">
                    <tr class="border-t border-ink-200 text-[12px]">
                        <td colspan="2" class="px-3 py-2 text-right text-ink-500">Total</td>
                        <td class="px-3 py-2 text-right mono font-semibold" x-text="fmt(korDebit)"></td>
                        <td class="px-3 py-2 text-right mono font-semibold" x-text="fmt(korKredit)"></td>
                        <td></td>
                    </tr>
                    <tr class="border-t border-ink-200 text-[11.5px]">
                        <td colspan="2" class="px-3 py-2 text-right text-ink-500">Selisih</td>
                        <td colspan="2" class="px-3 py-2 text-right mono" :class="korBalanced ? 'text-emerald-700 font-semibold' : 'text-red-600'" x-text="korBalanced ? 'Seimbang ✓' : fmt(Math.abs(korDebit - korKredit))"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="btn btn-ghost mt-3 text-[12px]" @click="addLineKor()"><x-icon name="plus" :size="12" /> Tambah Baris</button>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="koreksiOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving || !korBalanced" @click="simpanKoreksi()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Jurnal Koreksi'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>

    {{-- ===== Tab: Penyusutan ===== --}}
    <div x-show="tab === 'penyusutan'" x-cloak>
        <div class="card p-4 my-3 bg-brand-50 border-brand-200 flex items-center gap-3">
            <div class="text-brand-600"><x-icon name="calendar" :size="18" /></div>
            <div class="flex-1 text-[12.5px]">
                <div class="font-medium text-brand-800">Penyusutan otomatis setiap tanggal 1</div>
                <div class="text-brand-700/80">Sistem menghitung beban penyusutan bulanan tiap unit dan langsung mencatat ke jurnal.</div>
            </div>
            <button class="btn btn-primary text-[12px]" :disabled="saving" @click="jalankanDepresiasi()">
                <span x-text="saving ? 'Memproses...' : 'Jalankan Manual'"></span>
            </button>
        </div>
        <div class="card overflow-hidden">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:100px">Periode</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Genset</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Harga Per. (snap)</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Beban Bulan</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Akumulasi</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Nilai Buku</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:140px">Jurnal</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:100px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($d['jadwal_penyusutan'] as $r)
                        @php $g = $gensetById[$r['id_genset']] ?? null; @endphp
                        @continue(! $g)
                        <tr class="border-b border-ink-100 hoverable">
                            <td class="px-3 py-2.5 mono text-[12px]">{{ $r['periode_bulan'] }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium">{{ ($merekById[$g['id_merek']]['nama_merek'] ?? '—') }} {{ ($kategoriById[$g['id_kategori']]['kapasitas'] ?? '—') }}</div>
                                <div class="mono text-[11px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-right"><span class="mono text-[11.5px] text-ink-500">{{ fmtIDR($r['harga_perolehan']) }}</span></td>
                            <td class="px-3 py-2.5 text-right"><span class="mono font-medium">{{ fmtIDR($r['beban_penyusutan']) }}</span></td>
                            <td class="px-3 py-2.5 text-right"><span class="mono text-red-700">{{ fmtIDR($r['akumulasi_penyusutan']) }}</span></td>
                            <td class="px-3 py-2.5 text-right"><span class="mono text-brand-700 font-medium">{{ fmtIDR($r['nilai_buku']) }}</span></td>
                            <td class="px-3 py-2.5 mono text-[12px]">{{ $jurnalById[$r['id_jurnal']]['no_bukti'] ?? '—' }}</td>
                            <td class="px-3 py-2.5"><x-status-pill :status="$r['status_jurnal']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Drawer: Tambah Akun ===== --}}
    <x-drawer show="akunOpen" close="akunOpen = false" :width="600"
        title="Tambah Akun Baru"
        subtitle="Sub-akun baru di Bagan Akun · pastikan kode mengikuti pola hierarki (mis. 1-1002, 5-2002)">

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Kode Akun" :required="true" hint="Mis. 1-1002, 2-1002, 5-2002">
                    <input class="inp mono" x-model="akunBaru.kode_akun" placeholder="1-1002" />
                </x-form-field>
                <x-form-field label="Nama Akun" :required="true">
                    <input class="inp" x-model="akunBaru.nama_akun" placeholder="Mis. Bank Mandiri Operasional" />
                </x-form-field>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Kategori" :required="true">
                    <select class="inp" x-model="akunBaru.kategori_akun">
                        <option value="aset">Aset</option>
                        <option value="kewajiban">Kewajiban (Utang)</option>
                        <option value="ekuitas">Ekuitas (Modal)</option>
                        <option value="pendapatan">Pendapatan</option>
                        <option value="beban">Beban</option>
                    </select>
                </x-form-field>
                <x-form-field label="Saldo Normal" :required="true" hint="Aset/Beban biasanya Debit · Kewajiban/Ekuitas/Pendapatan biasanya Kredit">
                    <select class="inp" x-model="akunBaru.saldo_normal">
                        <option value="debit">Debit</option>
                        <option value="kredit">Kredit</option>
                    </select>
                </x-form-field>
            </div>

            <x-form-field label="Akun Induk (opsional)" hint="Pilih akun header agar muncul di hierarki yang benar">
                <select class="inp" x-model="akunBaru.kode_parent">
                    <option value="">— tanpa induk (akun header) —</option>
                    @foreach ($akun as $a)
                        <option value="{{ $a['kode_akun'] }}">{{ $a['kode_akun'] }} · {{ $a['nama_akun'] }}</option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field label="Sub Kategori (opsional)" hint="Label bebas: lancar, tetap, jangka_pendek, operasional, dll.">
                <input class="inp" x-model="akunBaru.sub_kategori" placeholder="lancar" />
            </x-form-field>

            <div class="card p-3 bg-brand-50 border-brand-200 text-[11.5px] text-brand-900">
                <div class="font-semibold mb-1">Pola kode standar Voltra</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <li><span class="mono">1-xxxx</span> Aset · <span class="mono">2-xxxx</span> Kewajiban · <span class="mono">3-xxxx</span> Ekuitas</li>
                    <li><span class="mono">4-xxxx</span> Pendapatan · <span class="mono">5-xxxx</span> Beban</li>
                </ul>
            </div>
        </div>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="akunOpen = false">Tutup</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanAkun()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Akun'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
