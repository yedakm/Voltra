<?php

namespace App\Services;

use App\Models\Genset;
use App\Models\JadwalPenyusutan;
use App\Models\PeriodeAkuntansi;
use Illuminate\Support\Facades\DB;

/**
 * Otomatisasi depresiasi metode Garis Lurus / Straight Line (Bab 2.5 TA).
 *
 *   D = (C − R) / N
 *   D = beban penyusutan, C = harga perolehan, R = nilai residu, N = umur ekonomis (bulan)
 *
 * Dijalankan tiap akhir bulan oleh Laravel Scheduler (alur 5 PRD).
 */
class DepreciationService
{
    public function __construct(protected JournalService $journal) {}

    /**
     * Hitung & posting penyusutan satu periode untuk seluruh genset aktif.
     *
     * @return array{unit:int,total_beban:float,id_jurnal:?int,skipped:bool}
     */
    public function runForPeriod(int $idPerusahaan, int $tahun, int $bulan): array
    {
        $periodeBulan = sprintf('%04d-%02d-01', $tahun, $bulan);

        // Idempotent — jangan dobel kalau periode ini sudah dihitung.
        $sudahAda = JadwalPenyusutan::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', $periodeBulan)->exists();
        if ($sudahAda) {
            return ['unit' => 0, 'total_beban' => 0.0, 'id_jurnal' => null, 'skipped' => true];
        }

        $periode = $this->journal->resolvePeriode($idPerusahaan, $periodeBulan);

        $gensets = Genset::where('id_perusahaan', $idPerusahaan)
            ->whereNotIn('status', ['terjual'])
            ->get();

        $totalBeban = 0.0;
        $pending = [];

        DB::transaction(function () use ($gensets, $idPerusahaan, $periode, $periodeBulan, &$totalBeban, &$pending) {
            foreach ($gensets as $g) {
                $umur = max(1, (int) $g->umur_ekonomis_aktual);
                $beban = round(((float) $g->harga_perolehan - (float) $g->nilai_residu_aktual) / $umur);
                $depreciable = (float) $g->harga_perolehan - (float) $g->nilai_residu_aktual;

                // akumulasi = penyusutan periode sebelumnya + periode ini
                $akumSebelum = (float) JadwalPenyusutan::where('id_genset', $g->id_genset)
                    ->where('periode_bulan', '<', $periodeBulan)->sum('beban_penyusutan');
                $akumulasi = min($akumSebelum + $beban, $depreciable);
                $bebanEfektif = max(0, $akumulasi - $akumSebelum);

                $row = JadwalPenyusutan::create([
                    'id_genset' => $g->id_genset,
                    'id_perusahaan' => $idPerusahaan,
                    'id_periode' => $periode->id_periode,
                    'periode_bulan' => $periodeBulan,
                    'harga_perolehan' => $g->harga_perolehan,
                    'nilai_residu' => $g->nilai_residu_aktual,
                    'umur_ekonomis_bulan' => $umur,
                    'beban_penyusutan' => $bebanEfektif,
                    'akumulasi_penyusutan' => $akumulasi,
                    'nilai_buku' => (float) $g->harga_perolehan - $akumulasi,
                    'status_jurnal' => 'pending',
                ]);

                $totalBeban += $bebanEfektif;
                $pending[] = $row;
            }
        });

        if ($totalBeban <= 0) {
            return ['unit' => $gensets->count(), 'total_beban' => 0.0, 'id_jurnal' => null, 'skipped' => false];
        }

        // Jurnal penyusutan: D Beban Penyusutan / K Akumulasi Penyusutan
        $jurnal = $this->journal->post(
            idPerusahaan: $idPerusahaan,
            jenisJurnal: 'penyusutan',
            tanggal: date('Y-m-t', strtotime($periodeBulan)),
            lines: [
                ['kode_akun' => '5-1001', 'debit' => $totalBeban, 'keterangan' => 'Beban penyusutan ' . $periodeBulan],
                ['kode_akun' => '1-2002', 'kredit' => $totalBeban, 'keterangan' => 'Akumulasi penyusutan'],
            ],
            keterangan: "Depresiasi bulanan $periodeBulan ({$gensets->count()} unit)",
            referensiTipe: 'scheduler',
        );

        foreach ($pending as $row) {
            $row->update(['id_jurnal' => $jurnal->id_jurnal, 'status_jurnal' => 'posted']);
        }

        return [
            'unit' => $gensets->count(),
            'total_beban' => $totalBeban,
            'id_jurnal' => $jurnal->id_jurnal,
            'skipped' => false,
        ];
    }
}
