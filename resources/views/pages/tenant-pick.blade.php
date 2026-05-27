<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Pilih Perusahaan — Voltra ERP</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ink-50">
<div class="min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-md bg-white rounded-lg shadow-sm border border-ink-100 p-8">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-md flex items-center justify-center" style="background:linear-gradient(135deg,#177f8a,#0b4e56)">
                <x-icon name="power" :size="20" />
            </div>
            <div>
                <div class="text-[16px] font-semibold text-ink-800">Voltra</div>
                <div class="text-[11px] text-ink-400 uppercase tracking-wider">Genset Rental ERP</div>
            </div>
        </div>

        <div class="mt-6 text-[18px] font-semibold text-ink-800">Pilih Perusahaan</div>
        <div class="text-[13px] text-ink-500 mt-1">
            Email Anda terdaftar di beberapa perusahaan. Pilih yang ingin Anda masuki.
        </div>

        @if ($errors->any())
            <div class="mt-4 text-[12px] bg-red-50 border border-red-200 text-red-700 rounded-md px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.pick.submit') }}" class="mt-6 space-y-2">
            @csrf
            @foreach ($candidates as $c)
                <button type="submit" name="id_pengguna" value="{{ $c->id_pengguna }}"
                        class="w-full flex items-center justify-between gap-3 border border-ink-200 hover:border-brand-500 hover:bg-brand-50/30 rounded-md px-4 py-3 text-left transition">
                    <div>
                        <div class="text-[13.5px] font-medium text-ink-800">{{ $c->perusahaan->nama_perusahaan ?? '—' }}</div>
                        <div class="text-[11.5px] text-ink-500 mt-0.5">
                            {{ $c->nama }} · <span class="uppercase tracking-wide">{{ $c->role }}</span>
                        </div>
                    </div>
                    <x-icon name="arrow" :size="14" />
                </button>
            @endforeach
        </form>

        <div class="mt-6 text-[12px] text-ink-500 text-center">
            Bukan Anda?
            <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:underline">Kembali ke login</a>
        </div>
    </div>
</div>
</body>
</html>
