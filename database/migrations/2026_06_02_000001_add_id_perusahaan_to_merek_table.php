<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Merek dijadikan milik per-perusahaan (tenant), bukan master global lagi.
 * Tiap perusahaan kini punya daftar merek sendiri dan tidak bisa melihat
 * merek milik perusahaan lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom dulu (nullable) supaya baris lama tidak menolak.
        Schema::table('merek', function (Blueprint $t) {
            $t->unsignedBigInteger('id_perusahaan')->nullable()->after('id_merek');
        });

        // 2. Backfill: tiap merek diberikan ke perusahaan pemilik genset yang
        //    memakainya. Bila tak ada genset yang memakai, jatuh ke perusahaan
        //    pertama (id terkecil) sebagai default.
        $fallback = DB::table('perusahaan')->min('id_perusahaan') ?? 1;
        foreach (DB::table('merek')->get() as $m) {
            $owner = DB::table('genset')->where('id_merek', $m->id_merek)->value('id_perusahaan');
            DB::table('merek')->where('id_merek', $m->id_merek)
                ->update(['id_perusahaan' => $owner ?? $fallback]);
        }

        // 3. Pasang foreign key + index.
        Schema::table('merek', function (Blueprint $t) {
            $t->foreign('id_perusahaan')->references('id_perusahaan')->on('perusahaan')->cascadeOnDelete();
            $t->index('id_perusahaan');
        });
    }

    public function down(): void
    {
        Schema::table('merek', function (Blueprint $t) {
            $t->dropForeign(['id_perusahaan']);
            $t->dropIndex(['id_perusahaan']);
            $t->dropColumn('id_perusahaan');
        });
    }
};
