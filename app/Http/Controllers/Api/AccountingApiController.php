<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailJurnal;
use App\Models\Genset;
use App\Models\JadwalPenyusutan;
use App\Models\JurnalAkuntansi;
use App\Models\PeriodeAkuntansi;
use App\Services\DepreciationService;
use App\Services\JournalService;
use App\Services\PeriodClosingService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * API akuntansi — depresiasi otomatis, tutup buku, & laporan keuangan.
 */
class AccountingApiController extends Controller
{
    /** POST /api/depreciation/run — jalankan depresiasi Garis Lurus. */
    public function runDepreciation(Request $request, DepreciationService $service)
    {
        $periode = $request->input('periode', date('Y-m'));
        [$tahun, $bulan] = array_map('intval', explode('-', $periode));

        $result = $service->runForPeriod($request->user()->id_perusahaan, $tahun, $bulan);

        return response()->json(['periode' => $periode] + $result);
    }

    /**
     * POST /api/journal/manual — jurnal manual (mis. setoran kas/modal).
     * Contoh memasukkan uang kas: baris 1 Debit 1-1001 (Kas), baris 2 Kredit 3-1001 (Modal).
     */
    public function storeManualJournal(Request $request, JournalService $journal)
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.kode_akun' => ['nullable', 'string'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.kredit' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Buang baris kosong (tanpa akun atau tanpa nominal).
        $lines = collect($data['lines'])
            ->filter(fn ($l) => ! empty($l['kode_akun'])
                && ((float) ($l['debit'] ?? 0) > 0 || (float) ($l['kredit'] ?? 0) > 0))
            ->values()->all();

        if (count($lines) < 2) {
            return response()->json(['message' => 'Minimal 2 baris akun terisi.'], 422);
        }

        try {
            $jurnal = $journal->post(
                idPerusahaan: $request->user()->id_perusahaan,
                jenisJurnal: 'manual',
                tanggal: $data['tanggal'],
                lines: $lines,
                keterangan: $data['keterangan'] ?? 'Jurnal manual',
                dibuatOleh: $request->user()->id_pengguna,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Jurnal manual tersimpan.', 'jurnal' => $jurnal->load('detail')], 201);
    }

    /**
     * POST /api/journal/{id}/update — edit baris jurnal yang sudah ada.
     * Hanya diizinkan bila periode jurnal masih aktif; bila sudah ditutup,
     * perubahan harus lewat jurnal koreksi.
     */
    public function updateJournal(Request $request, JournalService $journal, int $id)
    {
        $tid = $request->user()->id_perusahaan;
        $jurnal = JurnalAkuntansi::where('id_perusahaan', $tid)->findOrFail($id);

        $periode = PeriodeAkuntansi::where('id_perusahaan', $tid)->find($jurnal->id_periode);
        if ($periode && $periode->status === 'ditutup') {
            return response()->json([
                'message' => 'Periode jurnal ini sudah ditutup. Perubahan harus lewat jurnal koreksi.',
            ], 422);
        }

        $data = $request->validate([
            'keterangan' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.kode_akun' => ['nullable', 'string'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.kredit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.keterangan' => ['nullable', 'string'],
        ]);

        // Buang baris kosong (tanpa akun atau tanpa nominal).
        $lines = collect($data['lines'])
            ->filter(fn ($l) => ! empty($l['kode_akun'])
                && ((float) ($l['debit'] ?? 0) > 0 || (float) ($l['kredit'] ?? 0) > 0))
            ->values()->all();

        if (count($lines) < 2) {
            return response()->json(['message' => 'Minimal 2 baris akun terisi.'], 422);
        }

        try {
            $journal->replaceLines($jurnal, $lines, $data['keterangan'] ?? $jurnal->keterangan);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Jurnal ' . $jurnal->no_bukti . ' diperbarui.',
            'jurnal' => $jurnal->load('detail'),
        ]);
    }

    /**
     * POST /aksi/journal/koreksi — buat jurnal koreksi atas jurnal lain.
     * Jurnal koreksi = jurnal biasa (jenis 'koreksi') yang mereferensi jurnal
     * asal (referensi_tipe='jurnal'). Diposting di periode aktif — JournalService
     * menolak bila periode tanggal koreksi sudah ditutup. Jurnal asal TIDAK diubah,
     * sehingga jejak audit terjaga.
     */
    public function storeKoreksi(Request $request, JournalService $journal)
    {
        $tid = $request->user()->id_perusahaan;

        $data = $request->validate([
            'referensi_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.kode_akun' => ['nullable', 'string'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.kredit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.keterangan' => ['nullable', 'string'],
        ]);

        $asal = JurnalAkuntansi::where('id_perusahaan', $tid)->find($data['referensi_id']);
        if (! $asal) {
            return response()->json(['message' => 'Jurnal asal tidak ditemukan.'], 404);
        }

        $lines = collect($data['lines'])
            ->filter(fn ($l) => ! empty($l['kode_akun'])
                && ((float) ($l['debit'] ?? 0) > 0 || (float) ($l['kredit'] ?? 0) > 0))
            ->values()->all();

        if (count($lines) < 2) {
            return response()->json(['message' => 'Minimal 2 baris akun terisi.'], 422);
        }

        try {
            $jurnal = $journal->post(
                idPerusahaan: $tid,
                jenisJurnal: 'koreksi',
                tanggal: $data['tanggal'],
                lines: $lines,
                keterangan: ($data['keterangan'] ?? null) ?: ('Koreksi atas ' . $asal->no_bukti),
                referensiTipe: 'jurnal',
                referensiId: $asal->id_jurnal,
                dibuatOleh: $request->user()->id_pengguna,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Jurnal koreksi ' . $jurnal->no_bukti . ' dibuat.',
            'jurnal' => $jurnal->load('detail'),
        ], 201);
    }

    /** GET /api/period/{id}/validate — cek kelengkapan sebelum tutup buku. */
    public function validatePeriod(Request $request, PeriodClosingService $service, int $id)
    {
        $periode = PeriodeAkuntansi::where('id_perusahaan', $request->user()->id_perusahaan)
            ->findOrFail($id);

        return response()->json(['periode' => $periode] + $service->validate($periode));
    }

    /** POST /api/period/{id}/close — kunci periode (RBAC: akuntan/owner). */
    public function closePeriod(Request $request, PeriodClosingService $service, int $id)
    {
        $periode = PeriodeAkuntansi::where('id_perusahaan', $request->user()->id_perusahaan)
            ->findOrFail($id);

        try {
            $closed = $service->close($periode, $request->user()->id_pengguna);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Periode ditutup.', 'periode' => $closed]);
    }

    /** POST /api/period/{id}/reopen — buka kembali periode (RBAC: akuntan/owner). */
    public function reopenPeriod(Request $request, PeriodClosingService $service, int $id)
    {
        $periode = PeriodeAkuntansi::where('id_perusahaan', $request->user()->id_perusahaan)
            ->findOrFail($id);

        try {
            $reopened = $service->reopen($periode);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Periode dibuka kembali. Penjurnalan di bulan ini aktif lagi.',
            'periode' => $reopened,
        ]);
    }

    /** GET /api/reports/{type} — laba-rugi | neraca | arus-kas. */
    public function report(Request $request, string $type)
    {
        $tid = $request->user()->id_perusahaan;

        $sum = fn (string $prefix, string $kind) => (float) DetailJurnal::where('id_perusahaan', $tid)
            ->where('kode_akun', 'like', $prefix . '%')->sum($kind);

        if ($type === 'laba-rugi') {
            $pendapatan = ($sum('4-', 'kredit') - $sum('4-', 'debit'));
            $beban = ($sum('5-', 'debit') - $sum('5-', 'kredit'));

            return response()->json([
                'laporan' => 'Laba Rugi',
                'total_pendapatan' => $pendapatan,
                'total_beban' => $beban,
                'laba_bersih' => $pendapatan - $beban,
            ]);
        }

        if ($type === 'neraca') {
            $aktif = Genset::where('id_perusahaan', $tid)->where('status', '!=', 'terjual')->get();
            $akumulasi = (float) JadwalPenyusutan::where('id_perusahaan', $tid)->sum('beban_penyusutan');
            $asetTetap = (float) $aktif->sum('harga_perolehan');
            $kas = $sum('1-1001', 'debit') - $sum('1-1001', 'kredit');
            $piutang = $sum('1-1101', 'debit') - $sum('1-1101', 'kredit');

            return response()->json([
                'laporan' => 'Neraca',
                'kas_bank' => $kas,
                'piutang_usaha' => $piutang,
                'aset_tetap' => $asetTetap,
                'akumulasi_penyusutan' => $akumulasi,
                'aset_tetap_bersih' => $asetTetap - $akumulasi,
            ]);
        }

        if ($type === 'arus-kas') {
            $masuk = $sum('1-1001', 'debit');
            $keluar = $sum('1-1001', 'kredit');

            return response()->json([
                'laporan' => 'Arus Kas',
                'kas_masuk' => $masuk,
                'kas_keluar' => $keluar,
                'kas_bersih' => $masuk - $keluar,
            ]);
        }

        return response()->json(['message' => "Jenis laporan '$type' tidak dikenal."], 404);
    }
}
