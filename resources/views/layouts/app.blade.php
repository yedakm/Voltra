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
            ['id' => 'brands', 'label' => 'Merek (Global)', 'icon' => 'tag'],
            ['id' => 'users', 'label' => 'Pengguna', 'icon' => 'users'],
            ['id' => 'tenant', 'label' => 'Perusahaan', 'icon' => 'box'],
        ]],
    ];
    $active = $route ?? 'dashboard';
@endphp
<div class="flex min-h-screen">

    {{-- ===== Sidebar ===== --}}
    <aside class="bg-ink-900 text-white w-[232px] flex-shrink-0 flex flex-col" style="min-height:100vh">
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
        <header x-data class="bg-white border-b border-ink-200 px-6 h-14 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                @isset($breadcrumb)
                    <div class="flex items-center gap-1.5 text-[12px] text-ink-500">
                        @foreach ($breadcrumb as $i => $b)
                            @if ($i > 0)<x-icon name="chevronR" :size="12" class="text-ink-300" />@endif
                            <span class="{{ $i === count($breadcrumb) - 1 ? 'text-ink-800 font-medium' : '' }}">{{ $b }}</span>
                        @endforeach
                    </div>
                @endisset
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400"><x-icon name="search" :size="14" /></div>
                    <input class="inp pl-8 w-[260px]" placeholder="Cari aset, invoice, pelanggan..."
                           @keydown.enter="$store.toasts.push('Pencarian global akan segera tersedia.','info')" />
                </div>
                <button class="btn btn-ghost" title="Notifikasi"
                        @click="$store.toasts.push('Cek menu Dashboard untuk daftar hal yang perlu perhatian.','info')">
                    <x-icon name="bell" :size="15" />
                    <span class="w-2 h-2 bg-amber-500 rounded-full -ml-1 mt-[-10px]"></span>
                </button>
                <div class="h-6 w-px bg-ink-200 mx-1"></div>
                <div class="flex items-center gap-2 text-[12px] cursor-pointer hover:bg-ink-50 px-2 py-1 rounded"
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

        <div class="flex-1 p-6 overflow-x-auto">
            @yield('content')
        </div>
    </main>
</div>

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
