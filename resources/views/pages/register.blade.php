<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Daftar — Voltra ERP</title>
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
                Bergabung dengan Voltra.
            </div>
            <div class="text-ink-300 mt-3 text-[14px] leading-relaxed">
                Buat perusahaan baru (Anda jadi <code class="mono text-[11px] bg-white/10 px-1 rounded">owner</code>) atau bergabung ke perusahaan yang sudah ada.
            </div>
        </div>

        <div class="relative z-10 text-[11px] text-ink-500">© 2026 Voltra · v1.0</div>
        <div class="absolute -right-20 -bottom-20 w-[420px] h-[420px] rounded-full opacity-20" style="background:radial-gradient(circle, #177f8a 0%, transparent 70%)"></div>
    </div>

    {{-- ===== Register form ===== --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-white">
        <form method="POST" action="{{ route('register.submit') }}" class="w-full max-w-sm"
              x-data="{ loading: false, mode: '{{ old('mode', 'new') }}' }"
              @submit="loading = true">
            @csrf
            <div class="text-[20px] font-semibold text-ink-800">Daftar Akun</div>
            <div class="text-[13px] text-ink-500 mt-1">Pilih cara bergabung lalu isi data Anda.</div>

            @if ($errors->any())
                <div class="mt-4 text-[12px] bg-red-50 border border-red-200 text-red-700 rounded-md px-3 py-2">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-5 flex gap-2">
                <label class="flex-1 border rounded-md px-3 py-2 cursor-pointer text-[12.5px]"
                       :class="mode === 'new' ? 'border-brand-500 bg-brand-50/40 text-brand-800' : 'border-ink-200 text-ink-700'">
                    <input type="radio" name="mode" value="new" x-model="mode" class="mr-1.5" />
                    Buat perusahaan baru
                </label>
                <label class="flex-1 border rounded-md px-3 py-2 cursor-pointer text-[12.5px]"
                       :class="mode === 'join' ? 'border-brand-500 bg-brand-50/40 text-brand-800' : 'border-ink-200 text-ink-700'">
                    <input type="radio" name="mode" value="join" x-model="mode" class="mr-1.5" />
                    Gabung yang sudah ada
                </label>
            </div>

            <div class="mt-5 space-y-4">
                <div x-show="mode === 'new'" x-cloak>
                    <x-form-field label="Nama Perusahaan" :required="true" hint="Anda otomatis menjadi owner perusahaan ini">
                        <input class="inp" name="nama_perusahaan" value="{{ old('nama_perusahaan') }}" />
                    </x-form-field>
                </div>
                <div x-show="mode === 'join'" x-cloak>
                    <x-form-field label="Kode Undangan" :required="true"
                        hint="Minta kode 8 karakter dari owner/admin perusahaan Anda (menu Perusahaan)">
                        <input class="inp uppercase tracking-widest mono" name="kode_undangan" maxlength="12"
                               placeholder="MIS. A1B2C3D4" value="{{ old('kode_undangan') }}" autocomplete="off" />
                    </x-form-field>
                </div>
                <x-form-field label="Nama Lengkap" :required="true">
                    <input class="inp" name="nama" value="{{ old('nama') }}" />
                </x-form-field>
                <x-form-field label="Email" :required="true">
                    <input type="email" class="inp" name="email" value="{{ old('email') }}" />
                </x-form-field>
                <x-form-field label="Password" :required="true" hint="Minimal 8 karakter">
                    <input type="password" class="inp" name="password" />
                </x-form-field>
                <x-form-field label="Konfirmasi Password" :required="true">
                    <input type="password" class="inp" name="password_confirmation" />
                </x-form-field>
                <button type="submit" class="btn btn-primary w-full justify-center" :disabled="loading">
                    <span x-text="loading ? 'Memproses...' : 'Daftar'"></span>
                    <template x-if="!loading"><x-icon name="arrow" :size="14" /></template>
                </button>
            </div>
            <div class="mt-5 text-[12px] text-ink-500 text-center">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:underline">Masuk di sini</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
