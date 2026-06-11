@extends('layouts.app')

@section('content')
@php
    $kind = $kind ?? 'users';
    $genset = collect($d['genset']);
    $transaksi = collect($d['transaksi_sewa']);

    $masterType = ['customers' => 'pelanggan', 'suppliers' => 'supplier', 'brands' => 'merek', 'users' => 'pengguna'][$kind] ?? '';
    $labelBaru = ['customers' => 'Pelanggan', 'suppliers' => 'Supplier', 'brands' => 'Merek', 'users' => 'Pengguna', 'tenant' => 'Tenant'][$kind] ?? '';
    $formInit = [
        'customers' => "nama_perusahaan:'',pic_kontak:'',alamat_lengkap:'',npwp:'',no_telepon:'',email:''",
        'suppliers' => "nama_supplier:'',pic_kontak:'',no_telepon:'',email:'',alamat:''",
        'brands' => "nama_merek:'',negara_asal:'',keterangan:''",
        'users' => "nama:'',email:'',role:'operator',password:''",
        'tenant' => '',
    ][$kind] ?? '';
    // Kolom kunci & nama (untuk tombol edit/hapus per baris).
    $pkField = ['customers' => 'id_pelanggan', 'suppliers' => 'id_supplier', 'brands' => 'id_merek'][$kind] ?? 'id';
    $nameField = ['customers' => 'nama_perusahaan', 'suppliers' => 'nama_supplier', 'brands' => 'nama_merek'][$kind] ?? '';
    $canManage = in_array($kind, ['customers', 'suppliers', 'brands']);
@endphp

<div x-data="{
    createOpen: false, editOpen: false, editId: null, saving: false,
    form: { {!! $formInit !!} },
    editForm: { {!! $formInit !!} },
    simpan() {
        this.saving = true;
        window.voltraSave('/aksi/master/{{ $masterType }}', this.form, '{{ $labelBaru }} baru tersimpan.')
            .catch(() => this.saving = false);
    },
    mulaiEdit(row, id) {
        this.editId = id;
        this.editForm = JSON.parse(JSON.stringify(row));
        this.editOpen = true;
    },
    simpanEdit() {
        this.saving = true;
        window.voltraSave('/aksi/master/{{ $masterType }}/' + this.editId, this.editForm, '{{ $labelBaru }} diperbarui.')
            .catch(() => this.saving = false);
    },
    hapus(id, nama) {
        if (!confirm('Hapus ' + (nama || 'data ini') + '?\nTindakan ini tidak dapat dibatalkan.')) return;
        this.saving = true;
        window.voltraSave('/aksi/master/{{ $masterType }}/' + id + '/delete', {}, '{{ $labelBaru }} dihapus.')
            .catch(() => this.saving = false);
    },
}">

@if ($kind === 'customers')
    {{-- ===== Pelanggan ===== --}}
    <x-section-header title="Pelanggan" subtitle="Daftar pelanggan perusahaan Anda">
        <x-slot:actions>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Pelanggan Baru</button>
        </x-slot:actions>
    </x-section-header>
    <x-toolbar placeholder="Cari nama, PIC..." />
    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold">Perusahaan</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Telepon</th>
                    <th class="px-3 py-2.5 text-left font-semibold">NPWP</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Alamat</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Sewa Aktif</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($d['pelanggan'] as $r)
                    @php
                        $initials = collect(explode(' ', $r['nama_perusahaan']))->take(2)->map(fn ($w) => $w[0] ?? '')->join('');
                        $n = $transaksi->filter(fn ($s) => $s['id_pelanggan'] === $r['id_pelanggan'] && ! in_array($s['status_pesanan'], ['selesai', 'dibatalkan']))->count();
                    @endphp
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-brand-100 text-brand-700 flex items-center justify-center text-[11px] font-semibold">{{ $initials }}</div>
                                <div>
                                    <div class="font-medium">{{ $r['nama_perusahaan'] }}</div>
                                    <div class="text-[11px] text-ink-500">{{ $r['pic_kontak'] }} · {{ $r['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 mono">{{ $r['no_telepon'] }}</td>
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $r['npwp'] }}</td>
                        <td class="px-3 py-2.5"><span class="text-ink-600 text-[12px]">{{ $r['alamat_lengkap'] }}</span></td>
                        <td class="px-3 py-2.5 text-right">
                            @if ($n > 0)<span class="pill" style="background:#ecf7f8;color:#0b4e56">{{ $n }}</span>@else<span class="text-ink-400">—</span>@endif
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <button class="btn btn-ghost" style="padding:4px 8px" title="Edit"
                                @click="mulaiEdit(@js($r), {{ $r[$pkField] }})"><x-icon name="edit" :size="13" /></button>
                            <button class="btn btn-danger" style="padding:4px 8px" title="Hapus"
                                @click="hapus({{ $r[$pkField] }}, @js($r[$nameField]))"><x-icon name="trash" :size="13" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@elseif ($kind === 'suppliers')
    {{-- ===== Supplier ===== --}}
    <x-section-header title="Supplier" subtitle="Daftar pemasok perusahaan Anda">
        <x-slot:actions>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Supplier Baru</button>
        </x-slot:actions>
    </x-section-header>
    <x-toolbar placeholder="Cari supplier..." />
    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold">Supplier</th>
                    <th class="px-3 py-2.5 text-left font-semibold">PIC</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Telepon</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Alamat</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Aset Dipasok</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($d['supplier'] as $r)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5"><span class="font-medium">{{ $r['nama_supplier'] }}</span></td>
                        <td class="px-3 py-2.5"><div>{{ $r['pic_kontak'] }}</div><div class="text-[11px] text-ink-500">{{ $r['email'] }}</div></td>
                        <td class="px-3 py-2.5 mono">{{ $r['no_telepon'] }}</td>
                        <td class="px-3 py-2.5">{{ $r['alamat'] }}</td>
                        <td class="px-3 py-2.5 text-right">{{ $genset->where('id_supplier', $r['id_supplier'])->count() }} unit</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <button class="btn btn-ghost" style="padding:4px 8px" title="Edit"
                                @click="mulaiEdit(@js($r), {{ $r[$pkField] }})"><x-icon name="edit" :size="13" /></button>
                            <button class="btn btn-danger" style="padding:4px 8px" title="Hapus"
                                @click="hapus({{ $r[$pkField] }}, @js($r[$nameField]))"><x-icon name="trash" :size="13" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@elseif ($kind === 'brands')
    {{-- ===== Merek ===== --}}
    <x-section-header title="Merek Genset"
        subtitle="Daftar merek genset milik perusahaan Anda">
        <x-slot:actions>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Merek Baru</button>
        </x-slot:actions>
    </x-section-header>
    <x-toolbar placeholder="Cari merek..." />
    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold">Nama Merek</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Negara Asal</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Keterangan</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Total Unit</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($d['merek'] as $r)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5"><span class="font-medium text-[13px]">{{ $r['nama_merek'] }}</span></td>
                        <td class="px-3 py-2.5">{{ $r['negara_asal'] }}</td>
                        <td class="px-3 py-2.5"><span class="text-ink-600 text-[12px]">{{ $r['keterangan'] }}</span></td>
                        <td class="px-3 py-2.5 text-right">{{ $genset->where('id_merek', $r['id_merek'])->count() }} unit</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <button class="btn btn-ghost" style="padding:4px 8px" title="Edit"
                                @click="mulaiEdit(@js($r), {{ $r[$pkField] }})"><x-icon name="edit" :size="13" /></button>
                            <button class="btn btn-danger" style="padding:4px 8px" title="Hapus"
                                @click="hapus({{ $r[$pkField] }}, @js($r[$nameField]))"><x-icon name="trash" :size="13" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@elseif ($kind === 'tenant')
    {{-- ===== Perusahaan (Tenant) ===== --}}
    <x-section-header title="Perusahaan"
        subtitle="Daftar perusahaan pengguna Voltra. Status aktif menentukan akses login.">
        <x-slot:actions>
            <button class="btn btn-primary"
                @click="$store.toasts.push('Pendaftaran perusahaan baru dikelola oleh Super Admin.','info')">
                <x-icon name="plus" :size="14" /> Perusahaan Baru
            </button>
        </x-slot:actions>
    </x-section-header>
    <div class="grid grid-cols-3 gap-3 mb-5">
        <x-stat-card label="Total Tenant" :value="count($d['perusahaan'])" sub="terdaftar" />
        <x-stat-card label="Aktif" :value="collect($d['perusahaan'])->where('status_aktif', 1)->count()" tone="ok" />
        <x-stat-card label="Pengguna Total" :value="count($d['pengguna'])" />
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold">Perusahaan</th>
                    <th class="px-3 py-2.5 text-left font-semibold">NPWP</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Kode Undangan</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Telepon</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Alamat</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Tgl. Bergabung</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Langganan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($d['perusahaan'] as $r)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded bg-brand-100 text-brand-700 flex items-center justify-center text-[12px] font-semibold">{{ $r['logo'] }}</div>
                                <div>
                                    <div class="font-medium">{{ $r['nama_perusahaan'] }}</div>
                                    <div class="text-[11px] text-ink-500">{{ $r['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $r['npwp'] }}</td>
                        <td class="px-3 py-2.5">
                            <button class="mono text-[12px] bg-brand-50 text-brand-700 border border-brand-200 rounded px-2 py-0.5 tracking-widest"
                                title="Klik untuk salin, lalu bagikan ke karyawan agar bisa mendaftar ke perusahaan ini"
                                @click="navigator.clipboard.writeText('{{ $r['kode_undangan'] ?? '' }}'); $store.toasts.push('Kode undangan disalin.','info')">
                                {{ $r['kode_undangan'] ?? '—' }}
                            </button>
                        </td>
                        <td class="px-3 py-2.5 mono">{{ $r['no_telepon'] }}</td>
                        <td class="px-3 py-2.5"><span class="text-[12px] text-ink-600">{{ $r['alamat'] }}</span></td>
                        <td class="px-3 py-2.5">{{ fmtDate($r['tgl_bergabung']) }}</td>
                        <td class="px-3 py-2.5">
                            @if ($r['status_aktif'])<x-status-pill status="aktif" />@else<x-status-pill status="ditutup" label="Suspended" />@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@else
    {{-- ===== Pengguna ===== --}}
    <x-section-header title="Pengguna"
        subtitle="Tim yang punya akses ke sistem · 5 peran: owner, admin, operator, teknisi, akuntan">
        <x-slot:actions>
            <button class="btn btn-primary" @click="createOpen = true"><x-icon name="plus" :size="14" /> Undang Pengguna</button>
        </x-slot:actions>
    </x-section-header>
    <x-toolbar placeholder="Cari nama, email..." />
    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold">Pengguna</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Role</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Status</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Terakhir Login</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($d['pengguna'] as $r)
                    <tr class="border-b border-ink-100 hoverable">
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-ink-700 text-white flex items-center justify-center text-[11px] font-semibold">{{ $r['avatar'] }}</div>
                                <div>
                                    <div class="font-medium">{{ $r['nama'] }}</div>
                                    <div class="text-[11px] text-ink-500">{{ $r['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5"><x-status-pill :status="$r['role']" /></td>
                        <td class="px-3 py-2.5"><x-status-pill status="aktif" /></td>
                        <td class="px-3 py-2.5">24 Apr 2026, 08:14</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ===== Drawer tambah master data ===== --}}
@if (in_array($kind, ['customers', 'suppliers', 'brands', 'users']))
    <x-drawer show="createOpen" close="createOpen = false" :width="560"
        :title="$labelBaru . ' Baru'" subtitle="Lengkapi data lalu simpan.">
        <div class="space-y-4">
            @if ($kind === 'customers')
                <x-form-field label="Nama Perusahaan" :required="true"><input class="inp" x-model="form.nama_perusahaan" /></x-form-field>
                <x-form-field label="PIC Kontak"><input class="inp" x-model="form.pic_kontak" /></x-form-field>
                <x-form-field label="Alamat Lengkap"><textarea class="inp" rows="2" x-model="form.alamat_lengkap"></textarea></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="NPWP"><input class="inp" x-model="form.npwp" /></x-form-field>
                    <x-form-field label="No. Telepon"><input class="inp" x-model="form.no_telepon" /></x-form-field>
                </div>
                <x-form-field label="Email"><input type="email" class="inp" x-model="form.email" /></x-form-field>
            @elseif ($kind === 'suppliers')
                <x-form-field label="Nama Supplier" :required="true"><input class="inp" x-model="form.nama_supplier" /></x-form-field>
                <x-form-field label="PIC Kontak"><input class="inp" x-model="form.pic_kontak" /></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="No. Telepon"><input class="inp" x-model="form.no_telepon" /></x-form-field>
                    <x-form-field label="Email"><input type="email" class="inp" x-model="form.email" /></x-form-field>
                </div>
                <x-form-field label="Alamat"><textarea class="inp" rows="2" x-model="form.alamat"></textarea></x-form-field>
            @elseif ($kind === 'brands')
                <x-form-field label="Nama Merek" :required="true"><input class="inp" x-model="form.nama_merek" /></x-form-field>
                <x-form-field label="Negara Asal"><input class="inp" x-model="form.negara_asal" /></x-form-field>
                <x-form-field label="Keterangan"><textarea class="inp" rows="2" x-model="form.keterangan"></textarea></x-form-field>
            @else
                <x-form-field label="Nama Lengkap" :required="true"><input class="inp" x-model="form.nama" /></x-form-field>
                <x-form-field label="Email" :required="true"><input type="email" class="inp" x-model="form.email" /></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="Role" :required="true">
                        <select class="inp" x-model="form.role">
                            @foreach (['owner', 'admin', 'operator', 'teknisi', 'akuntan'] as $role)
                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </x-form-field>
                    <x-form-field label="Password" :required="true"><input type="password" class="inp" x-model="form.password" /></x-form-field>
                </div>
            @endif
        </div>
        <x-slot:footer>
            <button class="btn btn-ghost" @click="createOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpan()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
@endif

{{-- ===== Drawer edit master data ===== --}}
@if ($canManage)
    <x-drawer show="editOpen" close="editOpen = false" :width="560"
        :title="'Edit ' . $labelBaru" subtitle="Ubah data lalu simpan perubahan.">
        <div class="space-y-4">
            @if ($kind === 'customers')
                <x-form-field label="Nama Perusahaan" :required="true"><input class="inp" x-model="editForm.nama_perusahaan" /></x-form-field>
                <x-form-field label="PIC Kontak"><input class="inp" x-model="editForm.pic_kontak" /></x-form-field>
                <x-form-field label="Alamat Lengkap"><textarea class="inp" rows="2" x-model="editForm.alamat_lengkap"></textarea></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="NPWP"><input class="inp" x-model="editForm.npwp" /></x-form-field>
                    <x-form-field label="No. Telepon"><input class="inp" x-model="editForm.no_telepon" /></x-form-field>
                </div>
                <x-form-field label="Email"><input type="email" class="inp" x-model="editForm.email" /></x-form-field>
            @elseif ($kind === 'suppliers')
                <x-form-field label="Nama Supplier" :required="true"><input class="inp" x-model="editForm.nama_supplier" /></x-form-field>
                <x-form-field label="PIC Kontak"><input class="inp" x-model="editForm.pic_kontak" /></x-form-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-form-field label="No. Telepon"><input class="inp" x-model="editForm.no_telepon" /></x-form-field>
                    <x-form-field label="Email"><input type="email" class="inp" x-model="editForm.email" /></x-form-field>
                </div>
                <x-form-field label="Alamat"><textarea class="inp" rows="2" x-model="editForm.alamat"></textarea></x-form-field>
            @elseif ($kind === 'brands')
                <x-form-field label="Nama Merek" :required="true"><input class="inp" x-model="editForm.nama_merek" /></x-form-field>
                <x-form-field label="Negara Asal"><input class="inp" x-model="editForm.negara_asal" /></x-form-field>
                <x-form-field label="Keterangan"><textarea class="inp" rows="2" x-model="editForm.keterangan"></textarea></x-form-field>
            @endif
        </div>
        <x-slot:footer>
            <button class="btn btn-ghost" @click="editOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanEdit()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
@endif
</div>
@endsection
