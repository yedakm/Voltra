@extends('layouts.app')

@section('content')
@php
    $sub = $sub ?? 'pemeliharaan';
    $pemeliharaan = collect($d['pemeliharaan']);
    $sukuCadang = collect($d['suku_cadang']);
    $gensetById = $d['gensetById'];
    $merekById = $d['merekById'];
    $kategoriById = $d['kategoriById'];
    $penggunaById = $d['penggunaById'];
    $partById = $d['partById'];
    $detailPem = collect($d['detail_pemeliharaan']);
@endphp

<div x-data="{
    open: null, saving: false, createOpen: false, editing: null,
    wo: { id_genset: '', id_pengguna: '', jenis_servis: 'rutin', tgl_mulai_servis: '{{ date('Y-m-d') }}', biaya_jasa_eksternal: 0, keterangan: '' },
    edit: { jenis_servis: 'rutin', tgl_mulai_servis: '', biaya_jasa_eksternal: 0, keterangan: '' },
    addPart: { id_part: '', qty_digunakan: 1 },
    part: { nama_part: '', kode_sku: '', stok_tersedia: 0, harga_satuan: 0 },
    editPartOpen: false, editPartId: null,
    editPart: { nama_part: '', kode_sku: '', stok_tersedia: 0, harga_satuan: 0 },
    selesaikan(idWo) {
        if (this.saving) return;
        this.saving = true;
        window.voltraSave('/aksi/maintenance/' + idWo + '/complete',
            {}, 'Servis selesai · jurnal beban dibuat.').catch(() => this.saving = false);
    },
    simpanServis() {
        if (!this.wo.id_genset || !this.wo.id_pengguna) { this.$store.toasts.push('Genset & teknisi wajib dipilih','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/maintenance', this.wo, 'Work order servis dibuat.').catch(() => this.saving = false);
    },
    mulaiEdit(r) {
        this.editing = r.id;
        this.edit.jenis_servis = r.jenis_servis;
        this.edit.tgl_mulai_servis = r.tgl_mulai_servis;
        this.edit.biaya_jasa_eksternal = Number(r.biaya_jasa_eksternal) || 0;
        this.edit.keterangan = r.keterangan || '';
    },
    simpanEdit(idWo) {
        this.saving = true;
        window.voltraSave('/aksi/maintenance/' + idWo + '/update', this.edit,
            'Work order diperbarui.').catch(() => this.saving = false);
    },
    tambahPart(idWo) {
        if (!this.addPart.id_part || this.addPart.qty_digunakan < 1) {
            this.$store.toasts.push('Pilih suku cadang & qty minimal 1.','error'); return;
        }
        this.saving = true;
        window.voltraSave('/aksi/maintenance/' + idWo + '/part', this.addPart,
            'Pemakaian suku cadang dicatat.').catch(() => this.saving = false);
    },
    simpanPart() {
        if (!this.part.nama_part || !this.part.kode_sku) { this.$store.toasts.push('Nama & SKU part wajib diisi','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/master/suku-cadang', this.part, 'Suku cadang baru tersimpan.').catch(() => this.saving = false);
    },
    mulaiEditPart(row) {
        this.editPartId = row.id_part;
        this.editPart = { nama_part: row.nama_part, kode_sku: row.kode_sku, stok_tersedia: Number(row.stok_tersedia), harga_satuan: Number(row.harga_satuan) };
        this.editPartOpen = true;
    },
    simpanEditPart() {
        if (!this.editPart.nama_part || !this.editPart.kode_sku) { this.$store.toasts.push('Nama & SKU part wajib diisi','error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/master/suku-cadang/' + this.editPartId, this.editPart, 'Suku cadang diperbarui.').catch(() => this.saving = false);
    },
    hapusPart(id, nama) {
        if (!confirm('Hapus suku cadang ' + (nama || 'ini') + '?\nTindakan ini tidak dapat dibatalkan.')) return;
        this.saving = true;
        window.voltraSave('/aksi/master/suku-cadang/' + id + '/delete', {}, 'Suku cadang dihapus.').catch(() => this.saving = false);
    },
}">
    <x-section-header
        :title="$sub === 'parts' ? 'Suku Cadang' : 'Pemeliharaan'"
        :subtitle="$sub === 'parts' ? 'Stok suku cadang milik perusahaan' : 'Servis rutin, perbaikan, & overhaul unit — pemakaian suku cadang dikurangi otomatis dari stok'">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> {{ $sub === 'parts' ? 'Part Baru' : 'Buat Servis' }}</button>
        </x-slot:actions>
    </x-section-header>

    {{-- Tab bar doubles as route navigation --}}
    <div class="border-b border-ink-200 flex items-center gap-6 px-1">
        <a href="{{ route('maintenance') }}" class="tab {{ $sub !== 'parts' ? 'active' : '' }}">
            Pemeliharaan <span class="ml-1.5 text-[11px] text-ink-400">({{ $pemeliharaan->count() }})</span>
        </a>
        <a href="{{ route('parts') }}" class="tab {{ $sub === 'parts' ? 'active' : '' }}">
            Suku Cadang <span class="ml-1.5 text-[11px] text-ink-400">({{ $sukuCadang->count() }})</span>
        </a>
    </div>

    @if ($sub === 'parts')
        {{-- ===== Parts table ===== --}}
        <x-toolbar placeholder="Cari SKU, nama part..." />
        <div class="card overflow-hidden">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:140px">SKU</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Nama Part</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Harga Satuan</th>
                        <th class="px-3 py-2.5 text-right font-semibold" style="width:90px">Stok</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Status Stok</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Nilai Inventaris</th>
                        <th class="px-3 py-2.5 text-right font-semibold" style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sukuCadang as $p)
                        <tr class="border-b border-ink-100 hoverable">
                            <td class="px-3 py-2.5 mono text-[12px]">{{ $p['kode_sku'] }}</td>
                            <td class="px-3 py-2.5"><span class="font-medium">{{ $p['nama_part'] }}</span></td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ fmtIDR($p['harga_satuan']) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">
                                <span class="font-medium {{ $p['stok_tersedia'] < 5 ? 'text-red-700' : ($p['stok_tersedia'] < 10 ? 'text-amber-600' : 'text-ink-800') }}">{{ $p['stok_tersedia'] }}</span>
                            </td>
                            <td class="px-3 py-2.5">
                                @if ($p['stok_tersedia'] < 5)
                                    <span class="pill" style="background:#fef3f2;color:#b42318"><span class="dot" style="background:#e56362"></span>Kritis</span>
                                @elseif ($p['stok_tersedia'] < 10)
                                    <span class="pill" style="background:#fef3e8;color:#a6700f"><span class="dot" style="background:#d18b1f"></span>Rendah</span>
                                @else
                                    <span class="pill" style="background:#eef8ef;color:#1f6a34"><span class="dot" style="background:#3d9956"></span>Aman</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-medium">{{ fmtIDR($p['stok_tersedia'] * $p['harga_satuan']) }}</span></td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <button class="btn btn-ghost" style="padding:4px 8px" title="Edit"
                                    @click="mulaiEditPart(@js($p))"><x-icon name="edit" :size="13" /></button>
                                <button class="btn btn-danger" style="padding:4px 8px" title="Hapus"
                                    @click="hapusPart({{ $p['id_part'] }}, @js($p['nama_part']))"><x-icon name="trash" :size="13" /></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- ===== Work orders ===== --}}
        <x-toolbar placeholder="Cari work order...">
            <x-slot:filters>
                <button class="btn btn-ghost"><x-icon name="filter" :size="14" /> Filter jenis</button>
            </x-slot:filters>
        </x-toolbar>
        <div class="card overflow-hidden">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:100px">ID</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Genset</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Jenis Servis</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Teknisi</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Mulai</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Selesai</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Total Biaya</th>
                        <th class="px-3 py-2.5 text-left font-semibold" style="width:130px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pemeliharaan as $r)
                        @php
                            $g = $gensetById[$r['id_genset']];
                            $parts = $detailPem->where('id_pemeliharaan', $r['id_pemeliharaan']);
                            $partsCost = $parts->sum('subtotal_harga_part');
                        @endphp
                        <tr class="border-b border-ink-100 hoverable cursor-pointer" @click="open = {{ $r['id_pemeliharaan'] }}">
                            <td class="px-3 py-2.5 mono text-[12px]">WO-{{ str_pad($r['id_pemeliharaan'], 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium text-[12.5px]">{{ $merekById[$g['id_merek']]['nama_merek'] }} {{ $kategoriById[$g['id_kategori']]['kapasitas'] }}</div>
                                <div class="mono text-[11px] text-ink-500">{{ $g['nomor_seri'] }}</div>
                            </td>
                            <td class="px-3 py-2.5"><x-status-pill :status="$r['jenis_servis']" /></td>
                            <td class="px-3 py-2.5">{{ $penggunaById[$r['id_pengguna']]['nama'] ?? '' }}</td>
                            <td class="px-3 py-2.5">{{ fmtDate($r['tgl_mulai_servis']) }}</td>
                            <td class="px-3 py-2.5">{{ $r['tgl_selesai'] ? fmtDate($r['tgl_selesai']) : '—' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-medium">{{ fmtIDR($partsCost + $r['biaya_jasa_eksternal']) }}</span></td>
                            <td class="px-3 py-2.5"><x-status-pill :status="$r['status'] === 'Selesai' ? 'selesai' : 'maintenance'" :label="$r['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Work order drawers --}}
        @foreach ($pemeliharaan as $r)
            @php
                $g = $gensetById[$r['id_genset']];
                $parts = $detailPem->where('id_pemeliharaan', $r['id_pemeliharaan']);
                $partsCost = $parts->sum('subtotal_harga_part');
                $totalCost = $partsCost + $r['biaya_jasa_eksternal'];
            @endphp
            @php
                $woJson = [
                    'id' => $r['id_pemeliharaan'],
                    'jenis_servis' => $r['jenis_servis'],
                    'tgl_mulai_servis' => $r['tgl_mulai_servis'],
                    'biaya_jasa_eksternal' => $r['biaya_jasa_eksternal'],
                    'keterangan' => $r['keterangan'] ?? '',
                ];
                $isOngoing = $r['status'] !== 'Selesai';
            @endphp
            <x-drawer show="open === {{ $r['id_pemeliharaan'] }}" close="open = null; editing = null" :width="640"
                :title="'WO-'.str_pad($r['id_pemeliharaan'], 4, '0', STR_PAD_LEFT).' · '.lbl($r['jenis_servis'])"
                :subtitle="$merekById[$g['id_merek']]['nama_merek'].' · '.$g['nomor_seri']">

                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <x-status-pill :status="$r['jenis_servis']" />
                        <x-status-pill :status="$r['status'] === 'Selesai' ? 'selesai' : 'maintenance'" :label="$r['status']" />
                    </div>
                    @if ($isOngoing)
                        <button class="btn btn-ghost text-[12px]"
                                x-show="editing !== {{ $r['id_pemeliharaan'] }}"
                                @click="mulaiEdit({{ Illuminate\Support\Js::from($woJson) }})">
                            <x-icon name="edit" :size="13" /> Edit
                        </button>
                        <button class="btn btn-ghost text-[12px]"
                                x-show="editing === {{ $r['id_pemeliharaan'] }}" x-cloak
                                @click="editing = null">Batal Edit</button>
                    @endif
                </div>

                {{-- ===== Read mode ===== --}}
                <div x-show="editing !== {{ $r['id_pemeliharaan'] }}">
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <x-field label="Teknisi">{{ $penggunaById[$r['id_pengguna']]['nama'] ?? '' }}</x-field>
                        <x-field label="Periode">{{ fmtDate($r['tgl_mulai_servis']) }} – {{ $r['tgl_selesai'] ? fmtDate($r['tgl_selesai']) : '—' }}</x-field>
                        <x-field label="Biaya Jasa Eksternal"><span class="mono">{{ fmtIDR($r['biaya_jasa_eksternal']) }}</span></x-field>
                        <x-field label="Subtotal Part"><span class="mono">{{ fmtIDR($partsCost) }}</span></x-field>
                        <div class="col-span-2"><x-field label="Keterangan">{{ $r['keterangan'] ?: '—' }}</x-field></div>
                    </div>
                </div>

                {{-- ===== Edit mode ===== --}}
                <div x-show="editing === {{ $r['id_pemeliharaan'] }}" x-cloak class="mb-5">
                    <div class="card p-4 bg-amber-50/40 border-amber-200">
                        <div class="text-[11.5px] text-amber-900 mb-3">Edit data work order yang masih berjalan. Stok dipotong saat suku cadang ditambahkan; jurnal beban dibuat saat tombol "Selesaikan" ditekan.</div>
                        <div class="grid grid-cols-2 gap-3">
                            <x-form-field label="Jenis Servis">
                                <select class="inp" x-model="edit.jenis_servis">
                                    <option value="rutin">Rutin</option>
                                    <option value="perbaikan">Perbaikan</option>
                                    <option value="overhaul">Overhaul</option>
                                </select>
                            </x-form-field>
                            <x-form-field label="Tgl. Mulai Servis">
                                <input type="date" class="inp" x-model="edit.tgl_mulai_servis" />
                            </x-form-field>
                            <div class="col-span-2">
                                <x-form-field label="Biaya Jasa Eksternal" hint="Tambahkan biaya servis pihak luar (bengkel, dll.) — boleh diubah kapan saja sebelum WO selesai">
                                    <input type="number" min="0" class="inp" x-model.number="edit.biaya_jasa_eksternal" />
                                </x-form-field>
                            </div>
                            <div class="col-span-2">
                                <x-form-field label="Keterangan">
                                    <textarea class="inp" rows="2" x-model="edit.keterangan" placeholder="Catatan kondisi unit, temuan, dll."></textarea>
                                </x-form-field>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button class="btn btn-primary text-[12px]" :disabled="saving" @click="simpanEdit({{ $r['id_pemeliharaan'] }})">
                                <x-icon name="check" :size="13" />
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <div class="text-[12px] font-semibold text-ink-700 uppercase tracking-wider mb-2">Suku Cadang Terpakai</div>
                    <div class="card overflow-hidden">
                        <table class="w-full text-[12.5px]">
                            <thead class="bg-ink-50 text-[10.5px] uppercase text-ink-500">
                                <tr><th class="text-left px-3 py-2 font-semibold">Part</th><th class="text-right px-3 py-2 font-semibold">Qty</th><th class="text-right px-3 py-2 font-semibold">Subtotal</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($parts as $dp)
                                    @php $p = $partById[$dp['id_part']] ?? null; @endphp
                                    <tr class="border-t border-ink-100">
                                        <td class="px-3 py-2"><div class="font-medium">{{ $p['nama_part'] ?? '—' }}</div><div class="mono text-[11px] text-ink-500">{{ $p['kode_sku'] ?? '' }}</div></td>
                                        <td class="px-3 py-2 text-right mono">{{ $dp['qty_digunakan'] }}</td>
                                        <td class="px-3 py-2 text-right mono">{{ fmtIDR($dp['subtotal_harga_part']) }}</td>
                                    </tr>
                                @empty
                                    <tr class="border-t border-ink-100">
                                        <td colspan="3" class="px-3 py-3 text-center text-ink-500 italic text-[11.5px]">Belum ada pemakaian suku cadang.</td>
                                    </tr>
                                @endforelse
                                <tr class="border-t-2 border-ink-300 bg-ink-50">
                                    <td class="px-3 py-2 font-semibold" colspan="2">Total Biaya Servis</td>
                                    <td class="px-3 py-2 text-right font-semibold mono">{{ fmtIDR($totalCost) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($isOngoing)
                        <div class="card p-3 mt-2 bg-ink-50/50">
                            <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Tambah Pemakaian Suku Cadang</div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <select class="inp text-[12px]" x-model="addPart.id_part">
                                        <option value="">— pilih suku cadang —</option>
                                        @foreach ($sukuCadang as $sc)
                                            <option value="{{ $sc['id_part'] }}">{{ $sc['kode_sku'] }} · {{ $sc['nama_part'] }} (stok {{ $sc['stok_tersedia'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width:90px">
                                    <input type="number" min="1" class="inp text-[12px] text-right mono" x-model.number="addPart.qty_digunakan" placeholder="Qty" />
                                </div>
                                <button class="btn btn-ghost text-[12px]" :disabled="saving" @click="tambahPart({{ $r['id_pemeliharaan'] }})">
                                    <x-icon name="plus" :size="13" /> Tambah
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card p-4">
                    <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-500 mb-2">Preview Jurnal Pemeliharaan</div>
                    <table class="w-full text-[11.5px] mono">
                        <tbody>
                            <tr><td class="py-1">5-2001 Beban Servis &amp; Pemeliharaan</td><td class="text-right">{{ fmtIDR($totalCost) }}</td><td class="text-right">—</td></tr>
                            @if ($partsCost > 0)
                                <tr><td class="py-1 pl-4">1-1301 Persediaan Suku Cadang</td><td class="text-right">—</td><td class="text-right">{{ fmtIDR($partsCost) }}</td></tr>
                            @endif
                            @if ($r['biaya_jasa_eksternal'] > 0)
                                <tr><td class="py-1 pl-4">1-1001 Kas &amp; Bank</td><td class="text-right">—</td><td class="text-right">{{ fmtIDR($r['biaya_jasa_eksternal']) }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <button class="btn btn-ghost" @click="open = null; editing = null">Tutup</button>
                    @if ($isOngoing)
                        <button class="btn btn-primary" :disabled="saving" @click="selesaikan({{ $r['id_pemeliharaan'] }})">
                            <x-icon name="check" :size="14" /> <span x-text="saving ? 'Memproses...' : 'Selesaikan & Buat Jurnal'"></span>
                        </button>
                    @endif
                </x-slot:footer>
            </x-drawer>
        @endforeach
    @endif

    {{-- ===== Drawer: Buat Servis (work order) ===== --}}
    @if ($sub !== 'parts')
        <x-drawer show="createOpen" close="createOpen = false" :width="600"
            title="Buat Work Order Servis" subtitle="Jadwalkan servis & tugaskan teknisi">
            <div class="space-y-4">
                <x-form-field label="Unit Genset" :required="true">
                    <select class="inp" x-model="wo.id_genset">
                        <option value="">— pilih genset —</option>
                        @foreach ($d['genset'] as $g)
                            <option value="{{ $g['id_genset'] }}">{{ $g['nomor_seri'] }} · {{ $merekById[$g['id_merek']]['nama_merek'] ?? '' }}</option>
                        @endforeach
                    </select>
                </x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="Teknisi" :required="true">
                        <select class="inp" x-model="wo.id_pengguna">
                            <option value="">— pilih teknisi —</option>
                            @foreach (collect($d['pengguna'])->whereIn('role', ['teknisi', 'operator']) as $u)
                                <option value="{{ $u['id_pengguna'] }}">{{ $u['nama'] }}</option>
                            @endforeach
                        </select>
                    </x-form-field>
                    <x-form-field label="Jenis Servis" :required="true">
                        <select class="inp" x-model="wo.jenis_servis">
                            <option value="rutin">Rutin</option>
                            <option value="perbaikan">Perbaikan</option>
                            <option value="overhaul">Overhaul</option>
                        </select>
                    </x-form-field>
                    <x-form-field label="Tgl. Mulai Servis" :required="true"><input type="date" class="inp" x-model="wo.tgl_mulai_servis" /></x-form-field>
                    <x-form-field label="Biaya Jasa Eksternal"><input type="number" class="inp" x-model="wo.biaya_jasa_eksternal" /></x-form-field>
                </div>
                <x-form-field label="Keterangan"><textarea class="inp" rows="2" x-model="wo.keterangan"></textarea></x-form-field>
            </div>
            <x-slot:footer>
                <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
                <button class="btn btn-primary" :disabled="saving" @click="simpanServis()">
                    <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Buat Servis'"></span>
                </button>
            </x-slot:footer>
        </x-drawer>
    @else
        {{-- ===== Drawer: Part Baru ===== --}}
        <x-drawer show="createOpen" close="createOpen = false" :width="520"
            title="Suku Cadang Baru" subtitle="Tambah master part ke inventaris">
            <div class="space-y-4">
                <x-form-field label="Nama Part" :required="true"><input class="inp" x-model="part.nama_part" /></x-form-field>
                <x-form-field label="Kode SKU" :required="true"><input class="inp" x-model="part.kode_sku" placeholder="e.g. FLT-OIL-99" /></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="Stok Tersedia" :required="true"><input type="number" class="inp" x-model="part.stok_tersedia" /></x-form-field>
                    <x-form-field label="Harga Satuan" :required="true"><input type="number" class="inp" x-model="part.harga_satuan" /></x-form-field>
                </div>
            </div>
            <x-slot:footer>
                <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
                <button class="btn btn-primary" :disabled="saving" @click="simpanPart()">
                    <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Part'"></span>
                </button>
            </x-slot:footer>
        </x-drawer>

        {{-- ===== Drawer: Edit Suku Cadang ===== --}}
        <x-drawer show="editPartOpen" close="editPartOpen = false" :width="520"
            title="Edit Suku Cadang" subtitle="Ubah data part lalu simpan perubahan">
            <div class="space-y-4">
                <x-form-field label="Nama Part" :required="true"><input class="inp" x-model="editPart.nama_part" /></x-form-field>
                <x-form-field label="Kode SKU" :required="true"><input class="inp" x-model="editPart.kode_sku" placeholder="e.g. FLT-OIL-99" /></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="Stok Tersedia" :required="true"><input type="number" min="0" class="inp" x-model.number="editPart.stok_tersedia" /></x-form-field>
                    <x-form-field label="Harga Satuan" :required="true"><input type="number" min="0" class="inp" x-model.number="editPart.harga_satuan" /></x-form-field>
                </div>
            </div>
            <x-slot:footer>
                <button class="btn btn-ghost" @click="editPartOpen = false">Batal</button>
                <button class="btn btn-primary" :disabled="saving" @click="simpanEditPart()">
                    <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </x-slot:footer>
        </x-drawer>
    @endif
</div>
@endsection
