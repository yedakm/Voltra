<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>{{ $pageTitle ?? 'Voltra' }} — Voltra ERP</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $d = $d ?? \App\Support\VoltraData::all();
    $tenant = $d['TENANT'];
    $authUser = \Illuminate\Support\Facades\Auth::user();
    $currentUser = $authUser ? [
        'nama' => $authUser->nama,
        'role' => $authUser->role,
        'avatar' => $authUser->avatar ?: strtoupper(mb_substr($authUser->nama, 0, 2)),
    ] : null;
    $nav = [
        ['group' => 'Operasional', 'items' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['id' => 'rental', 'label' => 'Penyewaan', 'icon' => 'rental'],
            ['id' => 'calendar', 'label' => 'Kalender', 'icon' => 'calendar'],
            ['id' => 'handover', 'label' => 'Serah-Terima', 'icon' => 'arrow'],
            ['id' => 'maintenance', 'label' => 'Pemeliharaan', 'icon' => 'wrench'],
        ]],
        ['group' => 'Aset & Inventaris', 'items' => [
            ['id' => 'assets', 'label' => 'Genset', 'icon' => 'asset'],
            ['id' => 'disposal', 'label' => 'Pelepasan Aset', 'icon' => 'logout'],
            ['id' => 'parts', 'label' => 'Suku Cadang', 'icon' => 'box'],
        ]],
        ['group' => 'Keuangan', 'items' => [
            ['id' => 'invoices', 'label' => 'Invoice & Bayar', 'icon' => 'invoice'],
            ['id' => 'opex', 'label' => 'Beban Operasional', 'icon' => 'diesel'],
            ['id' => 'accounting', 'label' => 'Jurnal & COA', 'icon' => 'ledger'],
            ['id' => 'period', 'label' => 'Tutup Buku', 'icon' => 'check'],
            ['id' => 'reports', 'label' => 'Laporan', 'icon' => 'doc'],
        ]],
        ['group' => 'Master Data', 'items' => [
            ['id' => 'customers', 'label' => 'Pelanggan', 'icon' => 'users'],
            ['id' => 'suppliers', 'label' => 'Supplier', 'icon' => 'tag'],
            ['id' => 'brands', 'label' => 'Merek', 'icon' => 'tag'],
            ['id' => 'users', 'label' => 'Pengguna', 'icon' => 'users'],
            ['id' => 'tenant', 'label' => 'Perusahaan', 'icon' => 'box'],
        ]],
    ];
    $active = $route ?? 'dashboard';
@endphp
<div class="flex min-h-screen" x-data="{ sidebarOpen: false }">

    {{-- Backdrop drawer mobile --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 bg-ink-900/40 z-40 lg:hidden"
         @click="sidebarOpen = false"></div>

    {{-- ===== Sidebar (drawer di layar < lg) ===== --}}
    <aside class="bg-ink-900 text-white w-[232px] flex-shrink-0 flex flex-col min-h-screen
                  fixed inset-y-0 left-0 z-50 transform transition-transform duration-200
                  -translate-x-full lg:translate-x-0 lg:static"
           :class="sidebarOpen ? 'translate-x-0' : ''">
        <div class="px-4 py-4 border-b border-white/5 flex items-center gap-2">
            <div class="w-8 h-8 rounded-md flex items-center justify-center" style="background:linear-gradient(135deg,#177f8a,#0b4e56)">
                <x-icon name="power" :size="18" class="text-white" />
            </div>
            <div>
                <div class="text-[14px] font-semibold tracking-tight">Voltra</div>
                <div class="text-[10px] text-ink-400 uppercase tracking-wider">Genset Rental ERP</div>
            </div>
        </div>

        <nav class="flex-1 py-2 overflow-y-auto">
            @foreach ($nav as $g)
                <div class="mb-1">
                    <div class="nav-group">{{ $g['group'] }}</div>
                    <div class="px-2 flex flex-col gap-0.5">
                        @foreach ($g['items'] as $it)
                            <a href="{{ route($it['id']) }}" class="nav-link {{ $active === $it['id'] ? 'active' : '' }}">
                                <span class="nav-ic text-ink-400"><x-icon :name="$it['icon']" :size="15" /></span>
                                <span>{{ $it['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-white/5 p-3">
            <div class="flex items-center gap-2 px-2 py-2">
                <div class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-[12px] font-semibold">{{ $currentUser['avatar'] ?? '?' }}</div>
                <div class="flex-1 min-w-0">
                    <div class="text-[12px] text-white font-medium truncate">{{ $currentUser['nama'] ?? 'Tamu' }}</div>
                    <div class="text-[10px] text-ink-400 capitalize">{{ $currentUser['role'] ?? '—' }} · {{ implode(' ', array_slice(explode(' ', $tenant['nama_perusahaan'] ?? ''), 0, 2)) }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-ink-400 hover:text-white" title="Logout">
                        <x-icon name="logout" :size="15" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ===== Main ===== --}}
    <main class="flex-1 min-w-0 flex flex-col">
        <header x-data class="bg-white border-b border-ink-200 px-4 sm:px-6 h-14 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <button class="lg:hidden btn btn-ghost -ml-2 flex-shrink-0" title="Menu" @click="sidebarOpen = true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>
                @isset($breadcrumb)
                    <div class="flex items-center gap-1.5 text-[12px] text-ink-500">
                        @foreach ($breadcrumb as $i => $b)
                            @if ($i > 0)<x-icon name="chevronR" :size="12" class="text-ink-300" />@endif
                            <span class="{{ $i === count($breadcrumb) - 1 ? 'text-ink-800 font-medium' : '' }}">{{ $b }}</span>
                        @endforeach
                    </div>
                @endisset
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="relative hidden sm:block">
                    <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400"><x-icon name="search" :size="14" /></div>
                    <input class="inp pl-8 w-[160px] md:w-[260px]" placeholder="Cari aset, invoice, pelanggan..."
                           @keydown.enter="$store.toasts.push('Pencarian global akan segera tersedia.','info')" />
                </div>
                <button class="btn btn-ghost" title="Panduan penggunaan" @click="window.dispatchEvent(new CustomEvent('open-help'))">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </button>
                <button class="btn btn-ghost" title="Notifikasi"
                        @click="$store.toasts.push('Cek menu Dashboard untuk daftar hal yang perlu perhatian.','info')">
                    <x-icon name="bell" :size="15" />
                    <span class="w-2 h-2 bg-amber-500 rounded-full -ml-1 mt-[-10px]"></span>
                </button>
                <div class="h-6 w-px bg-ink-200 mx-1 hidden md:block"></div>
                <div class="hidden md:flex items-center gap-2 text-[12px] cursor-pointer hover:bg-ink-50 px-2 py-1 rounded"
                     @click="$store.toasts.push('Tenant aktif: {{ $tenant['nama_perusahaan'] }}. Ganti tenant via login.','info')">
                    <div class="w-6 h-6 rounded bg-brand-100 text-brand-700 flex items-center justify-center text-[10px] font-semibold">{{ $tenant['logo'] }}</div>
                    <div>
                        <div class="text-ink-800 font-medium leading-tight">{{ $tenant['nama_perusahaan'] }}</div>
                        <div class="text-[10px] text-ink-500 leading-tight">Tenant #{{ $tenant['id_perusahaan'] }} · IDR</div>
                    </div>
                    <x-icon name="chevron" :size="12" class="text-ink-400" />
                </div>
            </div>
        </header>

        <div class="flex-1 p-4 sm:p-6 overflow-x-auto">
            @yield('content')
        </div>
    </main>
</div>

{{-- ===== Panduan penggunaan ===== --}}
@php
    $panduan = [
        ['judul' => 'Mulai dari mana?', 'icon' => 'dashboard', 'langkah' => [
            'Pastikan data dasar sudah ada: genset di menu Genset, pelanggan di menu Pelanggan.',
            'Halaman Dashboard menampilkan ringkasan: pendapatan, aset, dan hal yang perlu perhatian.',
            'Semua angka keuangan terisi otomatis dari transaksi yang kamu catat. Tidak perlu membuat jurnal sendiri.',
        ]],
        ['judul' => 'Menyewakan genset', 'icon' => 'rental', 'langkah' => [
            'Buka menu Penyewaan, klik tombol Sewa Baru.',
            'Pilih pelanggan, pilih unit, isi tanggal mulai dan selesai sewa.',
            'Cek dulu menu Kalender bila ragu unit tersedia atau tidak. Sistem juga otomatis menolak jadwal yang bentrok.',
            'Simpan. Invoice dan jurnal pendapatan langsung dibuat otomatis.',
        ]],
        ['judul' => 'Menerima pembayaran', 'icon' => 'invoice', 'langkah' => [
            'Buka menu Invoice & Bayar, cari invoice pelanggan.',
            'Klik Catat Bayar, isi nominal (boleh sebagian untuk DP) dan metode bayar.',
            'Status berubah otomatis: DP bila sebagian, Lunas bila penuh.',
        ]],
        ['judul' => 'Kirim dan tarik unit', 'icon' => 'arrow', 'langkah' => [
            'Buka menu Serah-Terima, klik tombol tambah.',
            'Pilih kontrak, pilih jenis aktivitas: Pengambilan (kirim ke proyek) atau Pengembalian (tarik ke gudang).',
            'Isi nama PIC kedua pihak dan kondisi unit. Status serta lokasi genset terbarui otomatis.',
        ]],
        ['judul' => 'Servis dan suku cadang', 'icon' => 'wrench', 'langkah' => [
            'Buka menu Pemeliharaan, buat work order untuk unit yang diservis.',
            'Tambahkan suku cadang yang dipakai. Stok langsung terpotong.',
            'Klik Selesaikan bila servis beres. Biaya servis otomatis masuk pembukuan.',
        ]],
        ['judul' => 'Akhir bulan (akuntan)', 'icon' => 'check', 'langkah' => [
            'Penyusutan aset dihitung otomatis oleh sistem tiap awal bulan.',
            'Buka menu Tutup Buku, klik Tutup Buku pada bulan yang sudah selesai. Sistem memvalidasi dulu sebelum mengunci.',
            'Periode yang terkunci masih bisa dilihat lewat tombol Pratinjau, atau dibuka lagi dengan Buka Kembali bila perlu koreksi.',
            'Laporan laba rugi, neraca, dan arus kas selalu siap di menu Laporan.',
        ]],
        ['judul' => 'Mengundang karyawan', 'icon' => 'users', 'langkah' => [
            'Buka menu Perusahaan, klik kode undangan untuk menyalin.',
            'Bagikan kode itu ke karyawan. Mereka mendaftar lewat halaman Daftar, pilih Gabung yang sudah ada, lalu masukkan kodenya.',
            'Karyawan baru otomatis berperan operator. Ubah perannya di menu Pengguna bila perlu.',
        ]],
    ];
@endphp
@auth
<div x-data="{ open: false }" x-show="open" x-cloak class="fixed inset-0 z-[90] flex" style="display:none"
     @open-help.window="open = true" @keydown.escape.window="open = false">
    <div class="flex-1 bg-ink-900/30" @click="open = false"></div>
    <div class="bg-white shadow-pop flex flex-col fadein max-w-full" style="width:min(520px, 92vw)">
        <div class="px-5 py-4 border-b border-ink-200 flex items-start justify-between">
            <div>
                <div class="text-[16px] font-semibold text-ink-800">Panduan Penggunaan</div>
                <div class="text-[12px] text-ink-500 mt-0.5">Alur kerja sehari-hari di Voltra, langkah demi langkah.</div>
            </div>
            <button type="button" class="text-ink-400 hover:text-ink-700" @click="open = false">
                <x-icon name="x" :size="18" />
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-5 space-y-3">
            @foreach ($panduan as $i => $p)
                <div class="card overflow-hidden" x-data="{ buka: {{ $i === 0 ? 'true' : 'false' }} }">
                    <button class="w-full px-4 py-3 flex items-center gap-3 text-left hover:bg-ink-50" @click="buka = !buka">
                        <span class="w-7 h-7 rounded bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                            <x-icon :name="$p['icon']" :size="14" />
                        </span>
                        <span class="flex-1 text-[13px] font-semibold text-ink-800">{{ $p['judul'] }}</span>
                        <x-icon name="chevron" :size="14" class="text-ink-400" />
                    </button>
                    <div x-show="buka" x-cloak class="px-4 pb-4">
                        <ol class="list-decimal list-inside space-y-1.5 text-[12.5px] text-ink-600">
                            @foreach ($p['langkah'] as $l)
                                <li>{{ $l }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endforeach
            <div class="card p-3 bg-ink-50 text-[12px] text-ink-600">
                Menu yang tampil mengikuti peran akunmu. Bila sebuah menu tidak terlihat, berarti perannya belum punya akses. Owner dan admin bisa mengatur peran di menu Pengguna.
            </div>
        </div>
    </div>
</div>
@endauth

{{-- ===== Toast container ===== --}}
<div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-2" x-data>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div class="toast card px-4 py-3 min-w-[260px] flex items-center gap-2 shadow-pop"
             :class="t.kind === 'success' ? 'border-l-4 border-l-emerald-500' : (t.kind === 'error' ? 'border-l-4 border-l-red-500' : 'border-l-4 border-l-brand-500')">
            <div class="text-[13px] text-ink-800" x-text="t.msg"></div>
        </div>
    </template>
</div>

</body>
</html>
