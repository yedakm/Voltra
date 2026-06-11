<?php

namespace App\Services;

use App\Models\DetailJurnal;
use App\Models\JurnalAkuntansi;
use App\Models\PeriodeAkuntansi;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Otomatisasi penjurnalan double-entry (Bab 3.4.1 no.9 TA).
 * Setiap transaksi keuangan diposting ke jurnal_akuntansi + detail_jurnal
 * dengan validasi keseimbangan debit = kredit.
 */
class JournalService
{
    /**
     * Posting satu jurnal beserta baris detailnya.
     *
     * @param  array<int,array{kode_akun:string,debit?:float,kredit?:float,keterangan?:string}>  $lines
     */
    public function post(
        int $idPerusahaan,
        string $jenisJurnal,
        string $tanggal,
        array $lines,
        string $keterangan = '',
        ?string $referensiTipe = null,
        ?int $referensiId = null,
        ?int $dibuatOleh = null,
    ): JurnalAkuntansi {
        $totalDebit = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $totalKredit = round(array_sum(array_map(fn ($l) => (float) ($l['kredit'] ?? 0), $lines)), 2);

        if ($totalDebit !== $totalKredit) {
            throw new RuntimeException("Jurnal tidak seimbang: debit $totalDebit ≠ kredit $totalKredit");
        }

        $periode = $this->resolvePeriode($idPerusahaan, $tanggal);
        if ($periode->status === 'ditutup') {
            throw new RuntimeException('Periode akuntansi sudah ditutup. Penjurnalan ditolak.');
        }

        // Transaksi harus di koneksi voltra_akuntansi — DB::transaction tanpa
        // koneksi memakai default (voltra) dan tidak melindungi tabel jurnal.
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

            foreach (array_values($lines) as $i => $l) {
                DetailJurnal::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $l['kode_akun'],
                    'id_perusahaan' => $idPerusahaan,
                    'debit' => $l['debit'] ?? 0,
                    'kredit' => $l['kredit'] ?? 0,
                    'keterangan' => $l['keterangan'] ?? '',
                    'urutan' => $i + 1,
                ]);
            }

            return $jurnal;
        });
    }

    /**
     * Ganti seluruh baris detail sebuah jurnal yang sudah ada (untuk fitur edit).
     * Memvalidasi keseimbangan debit = kredit, lalu menulis ulang detail + total
     * header. Pengecekan periode ditutup dilakukan di controller.
     *
     * @param  array<int,array{kode_akun:string,debit?:float,kredit?:float,keterangan?:string}>  $lines
     */
    public function replaceLines(JurnalAkuntansi $jurnal, array $lines, ?string $keterangan = null): JurnalAkuntansi
    {
        $totalDebit = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $totalKredit = round(array_sum(array_map(fn ($l) => (float) ($l['kredit'] ?? 0), $lines)), 2);

        if ($totalDebit !== $totalKredit) {
            throw new RuntimeException("Jurnal tidak seimbang: debit $totalDebit ≠ kredit $totalKredit");
        }
        if ($totalDebit <= 0) {
            throw new RuntimeException('Total jurnal harus lebih dari 0.');
        }

        return DB::connection('voltra_akuntansi')->transaction(function () use ($jurnal, $lines, $totalDebit, $totalKredit, $keterangan) {
            DetailJurnal::where('id_jurnal', $jurnal->id_jurnal)->delete();

            foreach (array_values($lines) as $i => $l) {
                DetailJurnal::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $l['kode_akun'],
                    'id_perusahaan' => $jurnal->id_perusahaan,
                    'debit' => $l['debit'] ?? 0,
                    'kredit' => $l['kredit'] ?? 0,
                    'keterangan' => $l['keterangan'] ?? '',
                    'urutan' => $i + 1,
                ]);
            }

            $jurnal->update([
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'keterangan' => $keterangan ?? $jurnal->keterangan,
            ]);

            return $jurnal->refresh();
        });
    }

    /** Cari periode akuntansi yang memuat tanggal; buat bila belum ada. */
    public function resolvePeriode(int $idPerusahaan, string $tanggal): PeriodeAkuntansi
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

    protected function generateNoBukti(PeriodeAkuntansi $periode): string
    {
        $seq = JurnalAkuntansi::where('id_periode', $periode->id_periode)->count() + 1;

        return sprintf('JRN-%02d%02d0-%03d', $periode->tahun % 100, $periode->bulan, $seq);
    }
}
