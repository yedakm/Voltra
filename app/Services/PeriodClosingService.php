<?php

namespace App\Services;

use App\Models\JurnalAkuntansi;
use App\Models\PeriodeAkuntansi;
use RuntimeException;

/**
 * Tutup buku periode (alur 9 PRD / Bab 4.2.9 TA).
 * Memvalidasi kelengkapan jurnal sebelum periode dikunci.
 */
class PeriodClosingService
{
    /**
     * Validasi kelengkapan periode.
     *
     * @return array{checks:array<int,array{ok:bool,label:string}>,valid:bool}
     */
    public function validate(PeriodeAkuntansi $periode): array
    {
        $jurnal = JurnalAkuntansi::where('id_periode', $periode->id_periode)->get();
        $totalDebit = (float) $jurnal->sum('total_debit');
        $totalKredit = (float) $jurnal->sum('total_kredit');
        $balanced = round($totalDebit, 2) === round($totalKredit, 2);
        $depreciationPosted = $jurnal->contains('jenis_jurnal', 'penyusutan');

        $checks = [
            ['ok' => $balanced, 'label' => 'Total debit = kredit pada seluruh jurnal periode'],
            ['ok' => $depreciationPosted, 'label' => 'Jurnal penyusutan akhir bulan sudah ter-generate'],
            ['ok' => $jurnal->count() > 0, 'label' => $jurnal->count() . ' jurnal terverifikasi di periode ini'],
            ['ok' => $periode->status === 'aktif', 'label' => 'Periode belum pernah ditutup'],
        ];

        return [
            'checks' => $checks,
            'valid' => collect($checks)->every(fn ($c) => $c['ok']),
        ];
    }

    /** Kunci periode bila lolos validasi. */
    public function close(PeriodeAkuntansi $periode, int $ditutupOleh): PeriodeAkuntansi
    {
        if (! $this->validate($periode)['valid']) {
            throw new RuntimeException('Validasi tutup buku gagal — periode belum lengkap.');
        }

        $periode->update([
            'status' => 'ditutup',
            'tgl_tutup_buku' => now(),
            'ditutup_oleh' => $ditutupOleh,
        ]);

        return $periode->refresh();
    }
}
