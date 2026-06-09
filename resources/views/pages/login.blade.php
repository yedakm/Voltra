<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Masuk — Voltra ERP</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="min-h-screen flex">

    {{-- ===== Left brand panel ===== --}}
    <div class="hidden md:flex flex-1 bg-ink-900 text-white p-10 flex-col justify-between relative overflow-hidden">
        <div class="flex items-center gap-2 relative z-10">
            <div class="w-9 h-9 rounded-md flex items-center justify-center" style="background:linear-gradient(135deg,#177f8a,#0b4e56)">
                <x-icon name="power" :size="20" />
            </div>
            <div>
                <div class="text-[16px] font-semibold">Voltra</div>
                <div class="text-[11px] text-ink-400 uppercase tracking-wider">Genset Rental ERP</div>
            </div>
        </div>

        <div class="relative z-10 max-w-md">
            <div class="text-[28px] font-semibold leading-tight tracking-tight">
                ERP terpadu untuk bisnis sewa genset.
            </div>
            <div class="text-ink-300 mt-3 text-[14px] leading-relaxed">
                Kelola kontrak, pemeliharaan, dan akuntansi dalam satu platform — depresiasi otomatis tiap akhir bulan & jurnal terjurnal real-time.
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 text-[12.5px]">
                @foreach ([
                    ['Kalender Real-time', 'Cegah double booking'],
                    ['Jurnal Otomatis', 'Tiap transaksi langsung tercatat'],
                    ['Tutup Buku Periode', 'Kontrol per bulan'],
                    ['Multi Perusahaan', 'Data terpisah per perusahaan'],
                ] as $f)
                    <div class="border border-white/10 rounded-lg p-3">
                        <div class="text-white font-medium">{{ $f[0] }}</div>
                        <div class="text-ink-400 text-[11.5px] mt-0.5">{{ $f[1] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative z-10 text-[11px] text-ink-500">© 2026 Voltra · v1.0</div>
        <div class="absolute -right-20 -bottom-20 w-[420px] h-[420px] rounded-full opacity-20" style="background:radial-gradient(circle, #177f8a 0%, transparent 70%)"></div>
    </div>

    {{-- ===== Login form ===== --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-white">
        <form method="POST" action="{{ route('login.submit') }}" class="w-full max-w-sm"
              x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="text-[20px] font-semibold text-ink-800">Masuk ke Voltra</div>
            <div class="text-[13px] text-ink-500 mt-1">Masuk dengan email &amp; password akun Anda.</div>

            @if (session('status'))
                <div class="mt-4 text-[12px] bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-md px-3 py-2">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mt-4 text-[12px] bg-red-50 border border-red-200 text-red-700 rounded-md px-3 py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <x-form-field label="Email" :required="true">
                    <input class="inp" name="email" value="{{ old('email') }}" autofocus />
                </x-form-field>
                <x-form-field label="Password" :required="true">
                    <input type="password" class="inp" name="password" />
                </x-form-field>
                <button type="submit" class="btn btn-primary w-full justify-center" :disabled="loading">
                    <span x-text="loading ? 'Memuat...' : 'Masuk'"></span>
                    <template x-if="!loading"><x-icon name="arrow" :size="14" /></template>
                </button>
            </div>
            <div class="mt-5 text-[12px] text-ink-500 text-center">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:underline">Daftar di sini</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
