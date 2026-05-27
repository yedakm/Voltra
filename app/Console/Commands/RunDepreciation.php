<?php

namespace App\Console\Commands;

use App\Models\Perusahaan;
use App\Services\DepreciationService;
use Illuminate\Console\Command;

/**
 * Job otomatis depresiasi akhir bulan (alur 5 PRD).
 * Dijadwalkan via Laravel Scheduler — lihat routes/console.php.
 */
class RunDepreciation extends Command
{
    protected $signature = 'voltra:depreciate
        {--periode= : Periode YYYY-MM (default: bulan lalu)}
        {--perusahaan= : Batasi ke satu id_perusahaan}';

    protected $description = 'Hitung & posting penyusutan Garis Lurus untuk seluruh genset aktif';

    public function handle(DepreciationService $service): int
    {
        $periode = $this->option('periode') ?: date('Y-m', strtotime('first day of last month'));
        [$tahun, $bulan] = array_map('intval', explode('-', $periode));

        $tenants = Perusahaan::where('status_aktif', 1)
            ->when($this->option('perusahaan'), fn ($q) => $q->where('id_perusahaan', $this->option('perusahaan')))
            ->pluck('id_perusahaan');

        $this->info("Depresiasi periode $periode untuk {$tenants->count()} perusahaan...");

        foreach ($tenants as $pid) {
            $r = $service->runForPeriod($pid, $tahun, $bulan);
            $this->line($r['skipped']
                ? "  · Perusahaan #$pid — sudah dihitung, dilewati."
                : sprintf('  · Perusahaan #%d — %d unit, beban Rp %s, jurnal #%s',
                    $pid, $r['unit'], number_format($r['total_beban'], 0, ',', '.'), $r['id_jurnal'] ?? '-'));
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
