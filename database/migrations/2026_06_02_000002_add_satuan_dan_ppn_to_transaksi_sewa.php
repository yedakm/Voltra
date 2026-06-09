<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah fleksibilitas tarif sewa:
 * - satuan_sewa: harga sewa dihitung per HARI atau per BULAN.
 * - kena_ppn   : PPN 11% bisa dimatikan untuk transaksi tertentu.
 * Baris lama otomatis 'harian' + kena PPN (perilaku lama dipertahankan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_sewa', function (Blueprint $t) {
            $t->enum('satuan_sewa', ['harian', 'bulanan'])->default('harian')->after('pajak');
            $t->boolean('kena_ppn')->default(true)->after('satuan_sewa');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_sewa', function (Blueprint $t) {
            $t->dropColumn(['satuan_sewa', 'kena_ppn']);
        });
    }
};
