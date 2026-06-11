<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kode undangan per perusahaan — menutup celah privasi & keamanan di alur
 * registrasi: sebelumnya SEMUA perusahaan terdaftar tampil di dropdown dan
 * siapa pun bisa bergabung tanpa izin. Sekarang mode "gabung" mewajibkan
 * kode undangan yang hanya diketahui owner/admin perusahaan tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('voltra')->table('perusahaan', function (Blueprint $t) {
            $t->string('kode_undangan', 12)->nullable()->unique()->after('npwp');
        });

        // Generate kode untuk perusahaan yang sudah ada.
        foreach (DB::connection('voltra')->table('perusahaan')->whereNull('kode_undangan')->pluck('id_perusahaan') as $id) {
            DB::connection('voltra')->table('perusahaan')
                ->where('id_perusahaan', $id)
                ->update(['kode_undangan' => strtoupper(Str::random(8))]);
        }
    }

    public function down(): void
    {
        Schema::connection('voltra')->table('perusahaan', function (Blueprint $t) {
            $t->dropUnique(['kode_undangan']);
            $t->dropColumn('kode_undangan');
        });
    }
};
