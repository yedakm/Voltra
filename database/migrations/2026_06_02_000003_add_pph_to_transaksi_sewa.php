<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PPh 23 (2%) atas sewa. Dipotong pelanggan, dicatat sebagai kredit pajak.
 * - kena_pph : apakah transaksi dipotong PPh 23.
 * - pph      : nominal PPh yang dipotong (mengurangi kas yang diterima).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_sewa', function (Blueprint $t) {
            $t->decimal('pph', 15, 2)->default(0)->after('kena_ppn');
            $t->boolean('kena_pph')->default(false)->after('pph');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_sewa', function (Blueprint $t) {
            $t->dropColumn(['pph', 'kena_pph']);
        });
    }
};
