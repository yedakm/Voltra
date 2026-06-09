<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah nilai 'koreksi' ke enum jenis_jurnal agar jurnal koreksi bisa
 * dibedakan dari jurnal lain. Jurnal koreksi = jurnal biasa di tabel
 * jurnal_akuntansi yang mereferensi jurnal asal (referensi_tipe='jurnal'),
 * diposting di periode yang masih aktif — tanpa tabel baru.
 */
return new class extends Migration
{
    protected string $conn = 'voltra_akuntansi';

    protected array $base = [
        'pembelian_aset', 'sewa', 'pembayaran', 'pemeliharaan', 'beban_operasional',
        'penyusutan', 'penjualan_aset', 'penyesuaian', 'penutup', 'manual',
    ];

    public function up(): void
    {
        $this->setEnum([...$this->base, 'koreksi']);
    }

    public function down(): void
    {
        $this->setEnum($this->base);
    }

    protected function setEnum(array $values): void
    {
        $list = "'" . implode("','", $values) . "'";
        DB::connection($this->conn)->statement(
            "ALTER TABLE jurnal_akuntansi MODIFY jenis_jurnal ENUM($list) NOT NULL"
        );
    }
};
