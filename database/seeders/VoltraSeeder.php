<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Voltra seeder — TRUNCATE ONLY.
 * Aplikasi sekarang dipakai secara live, semua data di-input via UI.
 * Mock data referensi tetap tersedia di App\Support\VoltraData jika
 * sewaktu-waktu dibutuhkan untuk pengujian/demo.
 */
class VoltraSeeder extends Seeder
{
    public function run(): void
    {
        $ops = DB::connection('voltra');
        $acc = DB::connection('voltra_akuntansi');

        $ops->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['detail_pemeliharaan', 'pemeliharaan', 'suku_cadang', 'penjualan_genset',
            'pembayaran', 'pengembalian', 'detail_sewa', 'transaksi_sewa', 'jadwal_ketersediaan',
            'genset', 'pelanggan', 'supplier', 'kategori_genset', 'pengguna', 'merek', 'perusahaan'] as $tbl) {
            $ops->table($tbl)->truncate();
        }
        $ops->statement('SET FOREIGN_KEY_CHECKS=1');

        $acc->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['detail_jurnal', 'jurnal_akuntansi', 'periode_akuntansi', 'jadwal_penyusutan', 'akun_perkiraan'] as $tbl) {
            $acc->table($tbl)->truncate();
        }
        $acc->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Voltra: semua tabel dikosongkan (2 schema).');
    }
}
