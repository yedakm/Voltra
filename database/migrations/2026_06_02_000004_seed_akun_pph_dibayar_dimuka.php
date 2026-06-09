<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambahkan akun "PPh 23 Dibayar Dimuka" (1-1102) ke Chart of Accounts
 * setiap perusahaan. Akun ini wajib ada sebelum jurnal sewa ber-PPh diposting
 * (detail_jurnal punya FK ke akun_perkiraan).
 */
return new class extends Migration
{
    protected string $kode = '1-1102';

    public function up(): void
    {
        $tenants = DB::connection('voltra')->table('perusahaan')->pluck('id_perusahaan');

        foreach ($tenants as $tid) {
            $exists = DB::connection('voltra_akuntansi')->table('akun_perkiraan')
                ->where('id_perusahaan', $tid)->where('kode_akun', $this->kode)->exists();

            if (! $exists) {
                DB::connection('voltra_akuntansi')->table('akun_perkiraan')->insert([
                    'kode_akun' => $this->kode,
                    'id_perusahaan' => $tid,
                    'nama_akun' => 'PPh 23 Dibayar Dimuka',
                    'kategori_akun' => 'aset',
                    'sub_kategori' => 'lancar',
                    'saldo_normal' => 'debit',
                    'kode_parent' => '1-1',
                    'is_aktif' => 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::connection('voltra_akuntansi')->table('akun_perkiraan')
            ->where('kode_akun', $this->kode)->delete();
    }
};
