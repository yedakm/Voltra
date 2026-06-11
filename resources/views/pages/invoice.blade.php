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
    $totalApr = $invs->filter(fn ($i) => str_starts_with($i['tgl_terbit_invoice'], '2026-04'))->sum(fn ($i) => $i['total_tagihan'] + $i['pajak'] - ($i['pph'] ?? 0));
    $overdueCount = $invs->filter(fn ($i) => strtotime($i['tgl_jatuh_tempo']) < $today && $i['status_pembayaran'] !== 'lunas')->count();
@endphp

<div x-data="{
    tab: 'all', saving: false, open: null,
    pay: { id_sewa: null, no_invoice: '', total: 0, dibayar: 0, sisa: 0, nominal: 0, metode: 'transfer' },
    fmt: window.fmtIDR,
    bukaBayar(inv) {
        this.pay = { ...inv, nominal: inv.sisa, metode: 'transfer' };
        this.open = inv.id_sewa;
    },
    get statusBaru() {
        const tot = Number(this.pay.dibayar || 0) + Number(this.pay.nominal || 0);
        if (tot >= this.pay.total) return 'lunas';
        if (tot > 0) return 'dp';
        return 'belum_bayar';
    },
    simpanBayar() {
        const n = Number(this.pay.nominal || 0);
        if (n <= 0) { this.$store.toasts.push('Nominal bayar harus lebih dari 0', 'error'); return; }
        if (n > this.pay.sisa) { this.$store.toasts.push('Nominal melebihi sisa tagihan', 'error'); return; }
        this.saving = true;
        window.voltraSave('/aksi/payment', { id_sewa: this.pay.id_sewa, nominal_bayar: n, metode_bayar: this.pay.metode },
            'Pembayaran tercatat · jurnal Kas/Piutang dibuat.').catch(() => this.saving = false);
    },
    editOpen: false,
    ei: { id_sewa: null, no_invoice: '', satuan_sewa: 'harian', kena_ppn: true, kena_pph: false, tgl_jatuh_tempo: '', keterangan: '' },
    bukaEdit(inv) { this.ei = { ...inv }; this.editOpen = true; },
    simpanEdit() {
        this.saving = true;
        window.voltraSave('/aksi/rental/' + this.ei.id_sewa + '/update', {
            satuan_sewa: this.ei.satuan_sewa, kena_ppn: this.ei.kena_ppn, kena_pph: this.ei.kena_pph,
            tgl_jatuh_tempo: this.ei.tgl_jatuh_tempo, keterangan: this.ei.keterangan,
        }, r => 'Invoice ' + (r.sewa ? r.sewa.no_invoice : '') + ' diperbarui · jurnal disesuaikan.').catch(() => this.saving = false);
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
                    <th class="px-3 py-2.5 text-left font-semibold" style="width:160px">Aksi</th>
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
                        <td class="px-3 py-2.5 text-right tabular-nums"><span class="font-semibold">{{ fmtIDR($o['total']) }}</span>@if (($i['pph'] ?? 0) > 0)<div class="text-[10px] text-ink-400">net PPh</div>@endif</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">
                            @if ($o['sisa'] > 0)<span class="text-red-700 font-medium">{{ fmtIDR($o['sisa']) }}</span>@else<span class="text-ink-400">—</span>@endif
                        </td>
                        <td class="px-3 py-2.5"><x-status-pill :status="$i['status_pembayaran']" /></td>
                        <td class="px-3 py-2.5 whitespace-nowrap">
                            @if ($i['status_pembayaran'] === 'belum_bayar')
                                <button class="btn btn-ghost" style="padding:4px 7px" title="Edit invoice"
                                    @click="bukaEdit({ id_sewa: {{ $i['id_sewa'] }}, no_invoice: @js($i['no_invoice']), satuan_sewa: @js($i['satuan_sewa'] ?? 'harian'), kena_ppn: {{ ($i['kena_ppn'] ?? true) ? 'true' : 'false' }}, kena_pph: {{ ($i['kena_pph'] ?? false) ? 'true' : 'false' }}, tgl_jatuh_tempo: @js($i['tgl_jatuh_tempo']), keterangan: @js($i['keterangan'] ?? '') })"><x-icon name="edit" :size="13" /></button>
                            @endif
                            @if ($i['status_pembayaran'] !== 'lunas')
                                <button class="btn btn-ghost text-[11px]" :disabled="saving"
                                    @click="bukaBayar({ id_sewa: {{ $i['id_sewa'] }}, no_invoice: @js($i['no_invoice']), total: {{ $o['total'] }}, dibayar: {{ $o['paid'] }}, sisa: {{ $o['sisa'] }} })">Catat Bayar</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-drawer show="open !== null" close="open = null" :width="500" title="Catat Pembayaran">
        <x-slot:subtitle>
            <span class="mono" x-text="pay.no_invoice"></span>: isi sebagian untuk <b>DP</b>, atau penuh untuk lunas
        </x-slot:subtitle>

        <div class="space-y-4">
            <div class="card p-4 bg-ink-50 border-ink-200">
                <div class="space-y-1.5 text-[12.5px]">
                    <div class="flex justify-between text-ink-600"><span>Total Tagihan</span><span class="mono" x-text="fmt(pay.total)"></span></div>
                    <div class="flex justify-between text-ink-600"><span>Sudah Dibayar</span><span class="mono" x-text="fmt(pay.dibayar)"></span></div>
                    <div class="flex justify-between font-semibold text-ink-900 pt-1 border-t border-ink-200"><span>Sisa Tagihan</span><span class="mono text-red-700" x-text="fmt(pay.sisa)"></span></div>
                </div>
            </div>

            <x-form-field label="Nominal Bayar" :required="true" hint="Boleh kurang dari sisa (DP) atau penuh (lunas).">
                <input type="number" class="inp" x-model.number="pay.nominal" min="1" :max="pay.sisa" />
            </x-form-field>

            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn-ghost text-[11px]" @click="pay.nominal = Math.round(pay.sisa * 0.3)">DP 30%</button>
                <button type="button" class="btn btn-ghost text-[11px]" @click="pay.nominal = Math.round(pay.sisa * 0.5)">DP 50%</button>
                <button type="button" class="btn btn-ghost text-[11px]" @click="pay.nominal = pay.sisa">Lunasi (sisa penuh)</button>
            </div>

            <x-form-field label="Metode Bayar">
                <select class="inp" x-model="pay.metode">
                    <option value="transfer">Transfer</option>
                    <option value="tunai">Tunai</option>
                    <option value="giro">Giro</option>
                    <option value="kartu_kredit">Kartu Kredit</option>
                </select>
            </x-form-field>

            <div class="card p-3 flex items-center justify-between">
                <span class="text-[12px] text-ink-500">Status setelah pembayaran ini</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                    :class="statusBaru === 'lunas' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                    x-text="statusBaru === 'lunas' ? 'Lunas' : 'DP (Sebagian)'"></span>
            </div>
        </div>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="open = null">Batal</button>
            <button class="btn btn-primary" :disabled="saving || pay.nominal <= 0 || pay.nominal > pay.sisa" @click="simpanBayar()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Pembayaran'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>

    {{-- ===== Drawer: Edit Invoice ===== --}}
    <x-drawer show="editOpen" close="editOpen = false" :width="560" title="Edit Invoice">
        <x-slot:subtitle><span class="mono" x-text="ei.no_invoice"></span>: hanya invoice yang belum dibayar</x-slot:subtitle>

        <div class="space-y-4">
            <div class="card p-3 bg-amber-50 border-amber-200 text-[12px] text-amber-900">
                Mengubah satuan / PPN / PPh akan <b>menghitung ulang</b> tagihan dan <b>menulis ulang jurnal sewa</b> otomatis. Harga unit &amp; tanggal sewa diubah dari menu Penyewaan.
            </div>

            <x-form-field label="Satuan Sewa">
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="ei.satuan_sewa = 'harian'" class="p-2.5 rounded border text-left"
                        :class="ei.satuan_sewa === 'harian' ? 'border-brand-500 bg-brand-50' : 'border-ink-200'">
                        <div class="font-medium text-[13px]">Harian</div><div class="text-[11px] text-ink-500">Tarif per hari</div>
                    </button>
                    <button type="button" @click="ei.satuan_sewa = 'bulanan'" class="p-2.5 rounded border text-left"
                        :class="ei.satuan_sewa === 'bulanan' ? 'border-brand-500 bg-brand-50' : 'border-ink-200'">
                        <div class="font-medium text-[13px]">Bulanan</div><div class="text-[11px] text-ink-500">Tarif per bulan</div>
                    </button>
                </div>
            </x-form-field>

            <div class="grid grid-cols-2 gap-2">
                <label class="card p-3 flex items-center gap-2.5 cursor-pointer"><input type="checkbox" x-model="ei.kena_ppn" class="w-4 h-4" /><div class="text-[12.5px] font-medium">Kena PPN 11%</div></label>
                <label class="card p-3 flex items-center gap-2.5 cursor-pointer"><input type="checkbox" x-model="ei.kena_pph" class="w-4 h-4" /><div class="text-[12.5px] font-medium">Potong PPh 23 (2%)</div></label>
            </div>

            <x-form-field label="Jatuh Tempo"><input type="date" class="inp" x-model="ei.tgl_jatuh_tempo" /></x-form-field>
            <x-form-field label="Keterangan"><textarea class="inp" rows="2" x-model="ei.keterangan"></textarea></x-form-field>
        </div>

        <x-slot:footer>
            <button class="btn btn-ghost" @click="editOpen = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving" @click="simpanEdit()">
                <x-icon name="check" :size="14" /> <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
            </button>
        </x-slot:footer>
    </x-drawer>
</div>
@endsection
