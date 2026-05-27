<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailPemeliharaan;
use App\Models\DetailSewa;
use App\Models\Genset;
use App\Models\JadwalKetersediaan;
use App\Models\JadwalPenyusutan;
use App\Models\Pembayaran;
use App\Models\Pemeliharaan;
use App\Models\Pengembalian;
use App\Models\PenjualanGenset;
use App\Models\SukuCadang;
use App\Models\TransaksiSewa;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API transaksi operasional yang memicu penjurnalan otomatis.
 */
class TransactionApiController extends Controller
{
    public function __construct(protected JournalService $journal) {}

    /** POST /api/rental — buat sewa + invoice + jurnal Pendapatan/Piutang. */
    public function storeRental(Request $request)
    {
        $data = $request->validate([
            'id_pelanggan' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_genset' => ['required', 'integer'],
            'items.*.start_date' => ['required', 'date'],
            'items.*.end_date' => ['required', 'date'],
            'items.*.alamat_proyek' => ['nullable', 'string'],
            'items.*.harga_sewa_unit' => ['required', 'numeric'],
            'items.*.biaya_operator' => ['nullable', 'numeric'],
            'items.*.biaya_mobdemob' => ['nullable', 'numeric'],
            'items.*.biaya_bbm' => ['nullable', 'numeric'],
        ]);

        $user = $request->user();
        $tid = $user->id_perusahaan;

        $subSewa = 0;
        $subOpr = 0;
        foreach ($data['items'] as $it) {
            $days = max(1, (int) ceil((strtotime($it['end_date']) - strtotime($it['start_date'])) / 86400));
            $subSewa += (float) $it['harga_sewa_unit'] * $days;
            $subOpr += (float) ($it['biaya_operator'] ?? 0) + (float) ($it['biaya_mobdemob'] ?? 0) + (float) ($it['biaya_bbm'] ?? 0);
        }
        $subtotal = $subSewa + $subOpr;
        $pajak = round($subtotal * 0.11);

        $sewa = DB::connection('voltra')->transaction(function () use ($data, $tid, $user, $subtotal, $pajak) {
            $seq = TransaksiSewa::where('id_perusahaan', $tid)->count() + 1;
            $sewa = TransaksiSewa::create([
                'id_perusahaan' => $tid,
                'id_pelanggan' => $data['id_pelanggan'],
                'id_pengguna' => $user->id_pengguna,
                'no_referensi_kontrak' => sprintf('KTR-%s-%03d', date('Y'), $seq),
                'no_invoice' => sprintf('INV/%s/%03d', date('Y/m'), $seq),
                'tgl_pemesanan' => now()->toDateString(),
                'tgl_terbit_invoice' => now()->toDateString(),
                'tgl_jatuh_tempo' => now()->addDays(15)->toDateString(),
                'total_tagihan' => $subtotal,
                'pajak' => $pajak,
                'status_pesanan' => 'deal',
                'status_pembayaran' => 'belum_bayar',
            ]);
            foreach ($data['items'] as $it) {
                DetailSewa::create([
                    'id_sewa' => $sewa->id_sewa,
                    'id_genset' => $it['id_genset'],
                    'start_date' => $it['start_date'],
                    'end_date' => $it['end_date'],
                    'alamat_proyek' => $it['alamat_proyek'] ?? null,
                    'harga_sewa_unit' => $it['harga_sewa_unit'],
                    'biaya_operator' => $it['biaya_operator'] ?? 0,
                    'biaya_mobdemob' => $it['biaya_mobdemob'] ?? 0,
                    'biaya_bbm' => $it['biaya_bbm'] ?? 0,
                ]);
                // kunci kalender ketersediaan
                $cursor = strtotime($it['start_date']);
                $end = strtotime($it['end_date']);
                while ($cursor <= $end) {
                    JadwalKetersediaan::updateOrCreate(
                        ['id_genset' => $it['id_genset'], 'tanggal' => date('Y-m-d', $cursor)],
                        ['status' => 'disewa'],
                    );
                    $cursor = strtotime('+1 day', $cursor);
                }
            }

            return $sewa;
        });

        $jurnal = $this->journal->post(
            idPerusahaan: $tid,
            jenisJurnal: 'sewa',
            tanggal: now()->toDateString(),
            lines: [
                ['kode_akun' => '1-1101', 'debit' => $subtotal + $pajak, 'keterangan' => 'Piutang ' . $sewa->no_invoice],
                ['kode_akun' => '4-1001', 'kredit' => $subSewa, 'keterangan' => 'Pendapatan sewa'],
                ['kode_akun' => '4-1002', 'kredit' => $subOpr, 'keterangan' => 'Pendapatan operator & BBM'],
                ['kode_akun' => '2-2001', 'kredit' => $pajak, 'keterangan' => 'PPN 11% keluaran'],
            ],
            keterangan: 'Terbit Invoice ' . $sewa->no_invoice,
            referensiTipe: 'transaksi_sewa',
            referensiId: $sewa->id_sewa,
            dibuatOleh: $user->id_pengguna,
        );

        return response()->json(['sewa' => $sewa, 'jurnal' => $jurnal->load('detail')], 201);
    }

    /** POST /api/payment — catat pembayaran + jurnal Kas/Piutang. */
    public function storePayment(Request $request)
    {
        $data = $request->validate([
            'id_sewa' => ['required', 'integer'],
            'nominal_bayar' => ['required', 'numeric', 'min:1'],
            'metode_bayar' => ['required', 'in:transfer,tunai,giro,kartu_kredit'],
            'tgl_bayar' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $sewa = TransaksiSewa::where('id_perusahaan', $user->id_perusahaan)
            ->findOrFail($data['id_sewa']);
        $tglBayar = $data['tgl_bayar'] ?? now()->toDateString();

        $bayar = Pembayaran::create([
            'id_perusahaan' => $user->id_perusahaan,
            'id_sewa' => $sewa->id_sewa,
            'no_kuitansi' => sprintf('KWT-%s-%03d', date('ymd', strtotime($tglBayar)),
                Pembayaran::where('id_sewa', $sewa->id_sewa)->count() + 1),
            'tgl_bayar' => $tglBayar,
            'nominal_bayar' => $data['nominal_bayar'],
            'metode_bayar' => $data['metode_bayar'],
        ]);

        // perbarui status pembayaran
        $total = (float) $sewa->total_tagihan + (float) $sewa->pajak;
        $dibayar = (float) Pembayaran::where('id_sewa', $sewa->id_sewa)->sum('nominal_bayar');
        $sewa->update([
            'status_pembayaran' => $dibayar >= $total ? 'lunas' : ($dibayar > 0 ? 'dp' : 'belum_bayar'),
        ]);

        $jurnal = $this->journal->post(
            idPerusahaan: $user->id_perusahaan,
            jenisJurnal: 'pembayaran',
            tanggal: $tglBayar,
            lines: [
                ['kode_akun' => '1-1001', 'debit' => $data['nominal_bayar'], 'keterangan' => 'Penerimaan ' . $bayar->no_kuitansi],
                ['kode_akun' => '1-1101', 'kredit' => $data['nominal_bayar'], 'keterangan' => 'Pelunasan piutang'],
            ],
            keterangan: 'Pembayaran ' . $sewa->no_invoice,
            referensiTipe: 'pembayaran',
            referensiId: $bayar->id_pembayaran,
            dibuatOleh: $user->id_pengguna,
        );

        return response()->json(['pembayaran' => $bayar, 'sewa' => $sewa->refresh(), 'jurnal' => $jurnal], 201);
    }

    /** POST /api/asset-purchase — beli genset + jurnal Pembelian Aset Tetap. */
    public function storeAssetPurchase(Request $request)
    {
        $data = $request->validate([
            'id_kategori' => ['required', 'integer'],
            'id_merek' => ['nullable', 'integer'],
            'id_supplier' => ['nullable', 'integer'],
            'nomor_seri' => ['required', 'string'],
            'tgl_perolehan' => ['required', 'date'],
            'harga_perolehan' => ['required', 'numeric', 'min:1'],
            'nilai_residu_aktual' => ['nullable', 'numeric'],
            'umur_ekonomis_aktual' => ['nullable', 'integer'],
            'metode_bayar' => ['nullable', 'in:kas,utang'],
        ]);

        $user = $request->user();
        $genset = Genset::create([
            'id_perusahaan' => $user->id_perusahaan,
            'id_kategori' => $data['id_kategori'],
            'id_merek' => $data['id_merek'] ?? null,
            'id_supplier' => $data['id_supplier'] ?? null,
            'nomor_seri' => $data['nomor_seri'],
            'tgl_perolehan' => $data['tgl_perolehan'],
            'harga_perolehan' => $data['harga_perolehan'],
            'nilai_residu_aktual' => $data['nilai_residu_aktual'] ?? 0,
            'umur_ekonomis_aktual' => $data['umur_ekonomis_aktual'] ?? 96,
            'status' => 'di_gudang',
            'lokasi_terkini' => 'Gudang utama',
        ]);

        $akunKredit = ($data['metode_bayar'] ?? 'kas') === 'utang' ? '2-1001' : '1-1001';
        $jurnal = $this->journal->post(
            idPerusahaan: $user->id_perusahaan,
            jenisJurnal: 'pembelian_aset',
            tanggal: $data['tgl_perolehan'],
            lines: [
                ['kode_akun' => '1-2001', 'debit' => $data['harga_perolehan'], 'keterangan' => 'Genset ' . $genset->nomor_seri],
                ['kode_akun' => $akunKredit, 'kredit' => $data['harga_perolehan'], 'keterangan' => 'Pembayaran pembelian aset'],
            ],
            keterangan: 'Pembelian genset ' . $genset->nomor_seri,
            referensiTipe: 'genset',
            referensiId: $genset->id_genset,
            dibuatOleh: $user->id_pengguna,
        );

        return response()->json(['genset' => $genset, 'jurnal' => $jurnal->load('detail')], 201);
    }

    /** POST /api/asset-disposal — pelepasan aset + jurnal Laba/Rugi. */
    public function storeDisposal(Request $request)
    {
        $data = $request->validate([
            'id_genset' => ['required', 'integer'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'tgl_jual' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $genset = Genset::where('id_perusahaan', $user->id_perusahaan)->findOrFail($data['id_genset']);

        $akumulasi = (float) JadwalPenyusutan::where('id_genset', $genset->id_genset)->sum('beban_penyusutan');
        $nilaiBuku = (float) $genset->harga_perolehan - $akumulasi;
        $gainLoss = (float) $data['harga_jual'] - $nilaiBuku;
        $tglJual = $data['tgl_jual'] ?? now()->toDateString();

        $jual = PenjualanGenset::create([
            'id_perusahaan' => $user->id_perusahaan,
            'id_genset' => $genset->id_genset,
            'id_pengguna' => $user->id_pengguna,
            'tgl_jual' => $tglJual,
            'harga_jual' => $data['harga_jual'],
            'nilai_buku_saat_jual' => $nilaiBuku,
            'gain_loss' => $gainLoss,
            'keterangan' => $data['keterangan'] ?? ('Pelepasan ' . $genset->nomor_seri),
        ]);
        $genset->update(['status' => 'terjual']);

        $lines = [
            ['kode_akun' => '1-1001', 'debit' => $data['harga_jual'], 'keterangan' => 'Kas hasil penjualan'],
            ['kode_akun' => '1-2002', 'debit' => $akumulasi, 'keterangan' => 'Hapus akumulasi penyusutan'],
        ];
        if ($gainLoss < 0) {
            $lines[] = ['kode_akun' => '7-1001', 'debit' => abs($gainLoss), 'keterangan' => 'Rugi pelepasan aset'];
        }
        $lines[] = ['kode_akun' => '1-2001', 'kredit' => $genset->harga_perolehan, 'keterangan' => 'Hapus aset tetap'];
        if ($gainLoss > 0) {
            $lines[] = ['kode_akun' => '7-1001', 'kredit' => $gainLoss, 'keterangan' => 'Laba pelepasan aset'];
        }

        $jurnal = $this->journal->post(
            idPerusahaan: $user->id_perusahaan,
            jenisJurnal: 'penjualan_aset',
            tanggal: $tglJual,
            lines: $lines,
            keterangan: 'Pelepasan aset ' . $genset->nomor_seri,
            referensiTipe: 'penjualan_genset',
            referensiId: $jual->id_penjualan,
            dibuatOleh: $user->id_pengguna,
        );

        return response()->json(['penjualan' => $jual, 'jurnal' => $jurnal->load('detail')], 201);
    }

    /** POST /api/handover — catat serah-terima + update status & lokasi genset. */
    public function storeHandover(Request $request)
    {
        $data = $request->validate([
            'id_sewa' => ['required', 'integer'],
            'id_genset' => ['required', 'integer'],
            'jenis_aktivitas' => ['required', 'in:pengambilan,pengembalian'],
            'tanggal' => ['required', 'date'],
            'pic_dari_pelanggan' => ['nullable', 'string'],
            'pic_dari_rental' => ['nullable', 'string'],
            'kondisi_genset' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $genset = Genset::where('id_perusahaan', $user->id_perusahaan)->findOrFail($data['id_genset']);

        $ho = Pengembalian::create($data + ['dicatat_oleh' => $user->id_pengguna]);

        if ($data['jenis_aktivitas'] === 'pengambilan') {
            $det = DetailSewa::where('id_sewa', $data['id_sewa'])->where('id_genset', $data['id_genset'])->first();
            $genset->update([
                'status' => 'di_proyek',
                'lokasi_terkini' => $det->alamat_proyek ?? $genset->lokasi_terkini,
            ]);
        } else {
            $genset->update(['status' => 'di_gudang', 'lokasi_terkini' => 'Gudang utama']);
        }

        return response()->json(['pengembalian' => $ho, 'genset' => $genset->refresh()], 201);
    }

    /**
     * POST /api/genset/{id}/status — ubah status operasional genset.
     * Transisi yang diperbolehkan: di_gudang ↔ rusak, dan di_proyek → rusak
     * (bila unit rusak di lokasi proyek). Status terjual hanya boleh lewat
     * pelepasan aset; status di_proyek hanya boleh lewat serah-terima.
     */
    public function updateGensetStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:di_gudang,rusak'],
            'catatan' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $genset = Genset::where('id_perusahaan', $user->id_perusahaan)->findOrFail($id);

        if ($genset->status === 'terjual') {
            return response()->json(['message' => 'Unit sudah terjual, status tidak bisa diubah.'], 422);
        }

        $from = $genset->status;
        $to = $data['status'];

        $allowed = [
            'di_gudang' => ['rusak'],
            'di_proyek' => ['rusak'],
            'rusak' => ['di_gudang'],
            'di_perusahaan' => ['rusak'],
        ];

        if (! in_array($to, $allowed[$from] ?? [], true)) {
            return response()->json([
                'message' => "Status tidak bisa diubah dari '$from' ke '$to' lewat menu ini.",
            ], 422);
        }

        $updates = ['status' => $to];
        if ($to === 'rusak') {
            $updates['lokasi_terkini'] = 'Workshop / Bengkel';
        } elseif ($to === 'di_gudang') {
            $updates['lokasi_terkini'] = 'Gudang utama';
        }

        $genset->update($updates);

        return response()->json([
            'message' => 'Status unit diperbarui.',
            'genset' => $genset->refresh(),
        ]);
    }

    /**
     * POST /api/maintenance/{id}/update — edit work order yang masih berjalan.
     * Teknisi memakai ini untuk mencatat biaya jasa eksternal mendadak, mengubah
     * keterangan, atau memperbarui jenis servis. Ditolak bila servis sudah selesai.
     */
    public function updateMaintenance(Request $request, int $id)
    {
        $user = $request->user();
        $wo = Pemeliharaan::where('id_perusahaan', $user->id_perusahaan)->findOrFail($id);

        if ($wo->tgl_selesai) {
            return response()->json(['message' => 'Servis sudah selesai — tidak bisa diubah.'], 422);
        }

        $data = $request->validate([
            'jenis_servis' => ['sometimes', 'in:rutin,perbaikan,overhaul'],
            'tgl_mulai_servis' => ['sometimes', 'date'],
            'biaya_jasa_eksternal' => ['sometimes', 'numeric', 'min:0'],
            'keterangan' => ['sometimes', 'nullable', 'string'],
            'id_pengguna' => ['sometimes', 'integer'],
        ]);

        $wo->update($data);

        return response()->json(['message' => 'Work order diperbarui.', 'pemeliharaan' => $wo->refresh()]);
    }

    /**
     * POST /api/maintenance/{id}/part — tambah pemakaian suku cadang ke WO.
     * Stok dipotong saat completeMaintenance dipanggil; di sini hanya pencatatan.
     */
    public function addPartToMaintenance(Request $request, int $id)
    {
        $user = $request->user();
        $wo = Pemeliharaan::where('id_perusahaan', $user->id_perusahaan)->findOrFail($id);

        if ($wo->tgl_selesai) {
            return response()->json(['message' => 'Servis sudah selesai — tidak bisa menambah suku cadang.'], 422);
        }

        $data = $request->validate([
            'id_part' => ['required', 'integer'],
            'qty_digunakan' => ['required', 'integer', 'min:1'],
        ]);

        $part = SukuCadang::where('id_perusahaan', $user->id_perusahaan)->findOrFail($data['id_part']);

        if ($part->stok_tersedia < $data['qty_digunakan']) {
            return response()->json(['message' => 'Stok tidak mencukupi (' . $part->stok_tersedia . ' tersedia).'], 422);
        }

        $existing = DetailPemeliharaan::where('id_pemeliharaan', $id)->where('id_part', $data['id_part'])->first();
        if ($existing) {
            $newQty = $existing->qty_digunakan + $data['qty_digunakan'];
            $existing->update([
                'qty_digunakan' => $newQty,
                'subtotal_harga_part' => $newQty * (float) $part->harga_satuan,
            ]);
            $row = $existing;
        } else {
            $row = DetailPemeliharaan::create([
                'id_pemeliharaan' => $id,
                'id_part' => $data['id_part'],
                'qty_digunakan' => $data['qty_digunakan'],
                'subtotal_harga_part' => $data['qty_digunakan'] * (float) $part->harga_satuan,
            ]);
        }

        return response()->json(['message' => 'Pemakaian suku cadang dicatat.', 'detail' => $row], 201);
    }

    /** POST /api/maintenance/{id}/complete — selesaikan servis, potong stok, jurnal beban. */
    public function completeMaintenance(Request $request, int $id)
    {
        $user = $request->user();
        $wo = Pemeliharaan::where('id_perusahaan', $user->id_perusahaan)->findOrFail($id);

        if ($wo->tgl_selesai) {
            return response()->json(['message' => 'Servis ini sudah selesai.'], 422);
        }

        $parts = DetailPemeliharaan::where('id_pemeliharaan', $id)->get();
        $partsCost = (float) $parts->sum('subtotal_harga_part');
        $external = (float) $wo->biaya_jasa_eksternal;
        $total = $partsCost + $external;

        DB::connection('voltra')->transaction(function () use ($parts, $wo) {
            foreach ($parts as $p) {
                SukuCadang::where('id_part', $p->id_part)->decrement('stok_tersedia', $p->qty_digunakan);
            }
            $wo->update(['tgl_selesai' => now()->toDateString()]);
            Genset::where('id_genset', $wo->id_genset)->update(['status' => 'di_gudang']);
        });

        $jurnal = null;
        if ($total > 0) {
            $lines = [['kode_akun' => '5-2001', 'debit' => $total, 'keterangan' => 'Beban servis genset']];
            if ($partsCost > 0) {
                $lines[] = ['kode_akun' => '1-1301', 'kredit' => $partsCost, 'keterangan' => 'Pemakaian suku cadang'];
            }
            if ($external > 0) {
                $lines[] = ['kode_akun' => '1-1001', 'kredit' => $external, 'keterangan' => 'Pembayaran jasa eksternal'];
            }
            $jurnal = $this->journal->post(
                idPerusahaan: $user->id_perusahaan,
                jenisJurnal: 'pemeliharaan',
                tanggal: now()->toDateString(),
                lines: $lines,
                keterangan: 'Beban servis WO-' . str_pad((string) $wo->id_pemeliharaan, 4, '0', STR_PAD_LEFT),
                referensiTipe: 'pemeliharaan',
                referensiId: $wo->id_pemeliharaan,
                dibuatOleh: $user->id_pengguna,
            );
        }

        return response()->json(['pemeliharaan' => $wo->refresh(), 'jurnal' => $jurnal]);
    }

    /** POST /api/opex — catat beban operasional + jurnal Pengeluaran Kas. */
    public function storeOpex(Request $request)
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'kode_akun' => ['required', 'string'],
            'keterangan' => ['nullable', 'string'],
            'id_sewa' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $idSewa = $data['id_sewa'] ?? null;
        $jurnal = $this->journal->post(
            idPerusahaan: $user->id_perusahaan,
            jenisJurnal: 'beban_operasional',
            tanggal: $data['tanggal'],
            lines: [
                ['kode_akun' => $data['kode_akun'], 'debit' => $data['nominal'], 'keterangan' => $data['keterangan'] ?? 'Beban operasional'],
                ['kode_akun' => '1-1001', 'kredit' => $data['nominal'], 'keterangan' => 'Pengeluaran kas'],
            ],
            keterangan: $data['keterangan'] ?? 'Beban operasional',
            referensiTipe: $idSewa ? 'transaksi_sewa' : null,
            referensiId: $idSewa,
            dibuatOleh: $user->id_pengguna,
        );

        return response()->json(['jurnal' => $jurnal->load('detail')], 201);
    }
}
