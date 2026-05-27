@extends('layouts.app')

@section('content')
@php
    use App\Support\VoltraData;

    $today = strtotime('2026-04-24');
    $pelangganById = $d['pelangganById'];

    $invs = collect($d['transaksi_sewa'])->where('status_pesanan', '!=', 'dibatalkan');

    $tabs = [
        ['id' => 'all', 'label' => 'Semua', 'count' => $invs->count()],
        ['id' => 'unpaid', 'label' => 'Belum Lunas', 'count' => $invs->where('status_pembayaran', '!=', 'lunas')->count()],
        ['id' => 'dp', 'label' => 'Bayar DP', 'count' => $invs->where('status_pembayaran', 'dp')->count()],
        ['id' => 'overdue', 'label' => 'Overdue', 'count' => $invs->filter(fn ($i) => strtotime($i['tgl_jatuh_tempo']) < $today && $i['status_pembayaran'] !== 'lunas')->count()],
        ['id' => 'paid', 'label' => 'Lunas', 'count' => $invs->where('status_pembayaran', 'lunas')->count()],
    ];

    $outstanding = $invs->where('status_pembayaran', '!=', 'lunas')->sum(fn ($i) => VoltraData::sewaOutstanding($i)['sisa']);
    $paidThisMonth = collect($d['pembayaran'])->sum('nominal_bayar');
    $totalApr = $invs->filter(fn ($i) => str_starts_with($i['tgl_terbit_invoice'], '2026-04'))->sum(fn ($i) => $i['total_tagihan'] + $i['pajak']);
    $overdueCount = $invs->filter(fn ($i) => strtotime($i['tgl_jatuh_tempo']) < $today && $i['status_pembayaran'] !== 'lunas')->count();
@endphp

<div x-data="{
    tab: 'all', saving: false,
    bayar(idSewa, sisa) {
        if (sisa <= 0 || this.saving) return;
        this.saving = true;
        window.voltraSave('/aksi/payment', { id_sewa: idSewa, nominal_bayar: sisa, metode_bayar: 'transfer' },
            'Pembayaran tercatat · jurnal Kas/Piutang dibuat.').catch(() => this.saving = false);
    },
}">
    <x-section-header title="Invoice & Pembayaran"
        subtitle="Daftar invoice sewa dan pembayaran yang sudah masuk">
        <x-slot:actions>
            <button class="btn btn-ghost" @click="window.print()"><x-icon name="download" :size="14" /> Ekspor</button>
            <button class="btn btn-primary"
                @click="$store.toasts.push('Pilih invoice belum lunas di tabel, lalu klik Catat Bayar pada barisnya.','info')">
                <x-icon name="plus" :size="14" /> Catat Pembayaran</button>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-4 gap-3 mb-5">
        <x-stat-card label="Total Tertagih (Apr)" :value="fmtIDR($totalApr)" tone="brand" />
        <x-stat-card label="Sudah Dibayar (Apr)" :value="fmtIDR($paidThisMonth)" tone="ok" />
        <x-stat-card label="Piutang Terbuka" :value="fmtIDR($outstanding)" tone="warn" />
        <x-stat-card label="Overdue" value="{{ $overdueCount }} invoice" tone="danger" />
    </div>

    <x-tab-bar :tabs="$tabs" />
    <x-toolbar placeholder="Cari no. invoice, pelanggan..." />

    <div class="card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-ink-50 border-b border-ink-200 text-ink-500 text-[11px] uppercase tracking-wider">
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:160px">No. Invoice</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:140px">No. Kontrak</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:100px">Terbit</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:110px">Jatuh Tempo</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Pelanggan</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:140px">Total</th>
                    <th class="px-3 py-2.5 text-right font-semibold" style="width:140px">Sisa</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:120px">Status</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invs as $i)
                    @php
                        $o = VoltraData::sewaOutstanding($i);
                        $isOverdue = strtotime($i['tgl_jatuh_tempo']) < $today && $i['status_pembayaran'] !== 'lunas';
                        $rowTabs = ['all'];
                        if ($i['status_pembayaran'] !== 'lunas') $rowTabs[] = 'unpaid';
                        if ($i['status_pembayaran'] === 'lunas') $rowTabs[] = 'paid';
                        if ($i['status_pembayaran'] === 'dp') $rowTabs[] = 'dp';
                        if ($isOverdue) $rowTabs[] = 'overdue';
                    @endphp
                    <tr class="border-b border-ink-100 hoverable" x-show="@js($rowTabs).includes(tab)">
                        <td class="px-3 py-2.5 mono text-[12px]">{{ $i['no_invoice'] }}</td>
                        <td class="px-3 py-2.5 mono text-[12px] text-ink-500">{{ $i['no_referensi_kontrak'] }}</td>
                        <td class="px-3 py-2.5">{{ fmtDate($i['tgl_terbit_invoice']) }}</td>
                        <td class="px-3 py-2.5"><span class="{{ $isOverdue ? 'text-red-700 font-medium' : '' }}">{{ fmtDate($i['tgl_jatuh_tempo']) }}</span></td>
                        <td class="px-3 py-2.5">{{ $pelangganById[$i['id_pelanggan']]['nama_perusahaan'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-semibold">{{ fmtIDR($i['total_tagihan'] + $i['pajak']) }}</span></td>
                        <td class="px-3 py-2.5 text-right tabular-nums">
                            @if ($o['sisa'] > 0)<span class="text-red-700 font-medium">{{ fmtIDR($o['sisa']) }}</span>@else<span class="text-ink-400">—</span>@endif
                        </td>
                        <td class="px-3 py-2.5"><x-status-pill :status="$i['status_pembayaran']" /></td>
                        <td class="px-3 py-2.5">
                            @if ($i['status_pembayaran'] !== 'lunas')
                                <button class="btn btn-ghost text-[11px]" :disabled="saving"
                                    @click="bayar({{ $i['id_sewa'] }}, {{ $o['sisa'] }})">Catat Bayar</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
