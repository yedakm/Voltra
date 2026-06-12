<?php

namespace App\Services;

use App\Models\DetailJurnal;
use App\Models\JurnalAkuntansi;
use App\Models\PeriodeAkuntansi;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service untuk mencatat jurnal akuntansi double-entry.
 * Semua transaksi keuangan disimpan lewat service ini supaya
 * aturan debit = kredit selalu terjaga di satu tempat.
 */
class JournalService
{
    /**
     * Simpan satu jurnal beserta baris debit/kreditnya.
     * $lines berisi array baris, contoh:
     * [['kode_akun' => '1-1001', 'debit' => 500000], ['kode_akun' => '4-1001', 'kredit' => 500000]]
     */
    public function post($idPerusahaan, $jenisJurnal, $tanggal, array $lines, $keterangan = '', $referensiTipe = null, $referensiId = null, $dibuatOleh = null)
    {
        // Hitung total debit dan kredit dari semua baris.
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($lines as $baris) {
            $totalDebit += (float) ($baris['debit'] ?? 0);
            $totalKredit += (float) ($baris['kredit'] ?? 0);
        }
        $totalDebit = round($totalDebit, 2);
        $totalKredit = round($totalKredit, 2);

        // Aturan dasar akuntansi: total debit harus sama dengan total kredit.
        if ($totalDebit !== $totalKredit) {
            throw new RuntimeException("Jurnal tidak seimbang: debit $totalDebit tidak sama dengan kredit $totalKredit");
        }

        // Jurnal hanya boleh masuk ke periode yang masih aktif.
        $periode = $this->resolvePeriode($idPerusahaan, $tanggal);
        if ($periode->status === 'ditutup') {
            throw new RuntimeException('Periode akuntansi sudah ditutup. Penjurnalan ditolak.');
        }

        // Header dan detail disimpan dalam satu transaksi database
        // pada koneksi voltra_akuntansi, supaya kalau salah satu gagal
        // semuanya ikut dibatalkan (tidak ada jurnal setengah jadi).
        return DB::connection('voltra_akuntansi')->transaction(function () use (
            $idPerusahaan, $periode, $jenisJurnal, $tanggal, $lines,
            $totalDebit, $totalKredit, $keterangan, $referensiTipe, $referensiId, $dibuatOleh
        ) {
            $jurnal = JurnalAkuntansi::create([
                'id_perusahaan' => $idPerusahaan,
                'id_periode' => $periode->id_periode,
                'no_bukti' => $this->generateNoBukti($periode),
                'tanggal' => $tanggal,
                'jenis_jurnal' => $jenisJurnal,
                'referensi_tipe' => $referensiTipe,
                'referensi_id' => $referensiId,
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'keterangan' => $keterangan,
                'dibuat_oleh' => $dibuatOleh,
                'dibuat_pada' => now(),
            ]);

            $urutan = 1;
            foreach ($lines as $baris) {
                DetailJurnal::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $baris['kode_akun'],
                    'id_perusahaan' => $idPerusahaan,
                    'debit' => $baris['debit'] ?? 0,
                    'kredit' => $baris['kredit'] ?? 0,
                    'keterangan' => $baris['keterangan'] ?? '',
                    'urutan' => $urutan,
                ]);
                $urutan++;
            }

            return $jurnal;
        });
    }

    /**
     * Tulis ulang baris detail sebuah jurnal yang sudah ada (untuk fitur edit).
     * Pengecekan periode ditutup dilakukan di controller sebelum memanggil ini.
     */
    public function replaceLines(JurnalAkuntansi $jurnal, array $lines, $keterangan = null)
    {
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($lines as $baris) {
            $totalDebit += (float) ($baris['debit'] ?? 0);
            $totalKredit += (float) ($baris['kredit'] ?? 0);
        }
        $totalDebit = round($totalDebit, 2);
        $totalKredit = round($totalKredit, 2);

        if ($totalDebit !== $totalKredit) {
            throw new RuntimeException("Jurnal tidak seimbang: debit $totalDebit tidak sama dengan kredit $totalKredit");
        }
        if ($totalDebit <= 0) {
            throw new RuntimeException('Total jurnal harus lebih dari 0.');
        }

        return DB::connection('voltra_akuntansi')->transaction(function () use ($jurnal, $lines, $totalDebit, $totalKredit, $keterangan) {
            // Hapus baris lama lalu tulis baris baru.
            DetailJurnal::where('id_jurnal', $jurnal->id_jurnal)->delete();

            $urutan = 1;
            foreach ($lines as $baris) {
                DetailJurnal::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $baris['kode_akun'],
                    'id_perusahaan' => $jurnal->id_perusahaan,
                    'debit' => $baris['debit'] ?? 0,
                    'kredit' => $baris['kredit'] ?? 0,
                    'keterangan' => $baris['keterangan'] ?? '',
                    'urutan' => $urutan,
                ]);
                $urutan++;
            }

            $jurnal->update([
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'keterangan' => $keterangan ?? $jurnal->keterangan,
            ]);

            return $jurnal->refresh();
        });
    }

    /**
     * Cari periode akuntansi yang memuat tanggal tersebut.
     * Kalau periodenya belum ada, buat baru dengan status aktif.
     */
    public function resolvePeriode($idPerusahaan, $tanggal)
    {
        $tahun = (int) date('Y', strtotime($tanggal));
        $bulan = (int) date('n', strtotime($tanggal));

        return PeriodeAkuntansi::firstOrCreate(
            ['id_perusahaan' => $idPerusahaan, 'tahun' => $tahun, 'bulan' => $bulan],
            [
                'tgl_mulai' => sprintf('%04d-%02d-01', $tahun, $bulan),
                'tgl_selesai' => date('Y-m-t', strtotime($tanggal)),
                'status' => 'aktif',
            ],
        );
    }

    /**
     * Buat nomor bukti berurutan per periode, contoh: JRN-26060-001.
     */
    protected function generateNoBukti(PeriodeAkuntansi $periode)
    {
        $urutanKe = JurnalAkuntansi::where('id_periode', $periode->id_periode)->count() + 1;

        return sprintf('JRN-%02d%02d0-%03d', $periode->tahun % 100, $periode->bulan, $urutanKe);
    }
}
