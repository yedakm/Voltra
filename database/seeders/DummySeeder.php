<?php

namespace Database\Seeders;

use App\Support\VoltraData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Dummy data untuk demo/pengujian.
 * Akun utama: yeda@gmail.com (owner, perusahaan #1), password 12345678.
 * Memakai dataset VoltraData lalu dirapikan agar pas dengan kolom DB.
 */
class DummySeeder extends Seeder
{
    public function run(): void
    {
        $ops = DB::connection('voltra');
        $acc = DB::connection('voltra_akuntansi');

        // 1. Kosongkan semua tabel dulu (dua schema).
        $this->call(VoltraSeeder::class);

        $pw = Hash::make('12345678');

        // 2. perusahaan — minimal 3 (data inti ada di perusahaan #1).
        $perusahaan = VoltraData::perusahaan();
        $perusahaan[] = [
            'id_perusahaan' => 3, 'nama_perusahaan' => 'PT Daya Prima Energi', 'logo' => 'DPE',
            'alamat' => 'Surabaya, Jawa Timur', 'no_telepon' => '031-5550777', 'email' => 'info@dayaprima.id',
            'npwp' => '06.789.012.3-006.000', 'tgl_bergabung' => '2025-09-01', 'status_aktif' => 1,
        ];
        $ops->table('perusahaan')->insert($perusahaan);

        // 3. pengguna — tambah password (kolom NOT NULL) + akun yeda owner.
        $pengguna = array_map(fn ($u) => $u + ['password' => $pw], VoltraData::pengguna());
        $pengguna[] = [
            'id_pengguna' => 7, 'id_perusahaan' => 1, 'nama' => 'Yeda', 'email' => 'yeda@gmail.com',
            'password' => $pw, 'role' => 'owner', 'avatar' => 'YE',
        ];
        $pengguna[] = [
            'id_pengguna' => 8, 'id_perusahaan' => 2, 'nama' => 'Owner Borneo', 'email' => 'owner@mgb.id',
            'password' => $pw, 'role' => 'owner', 'avatar' => 'OB',
        ];
        $pengguna[] = [
            'id_pengguna' => 9, 'id_perusahaan' => 3, 'nama' => 'Owner Prima', 'email' => 'owner@dayaprima.id',
            'password' => $pw, 'role' => 'owner', 'avatar' => 'OP',
        ];
        $ops->table('pengguna')->insert($pengguna);

        // 4. Master & transaksi operasional (urut sesuai dependensi FK).
        $ops->table('merek')->insert(VoltraData::merek());
        $ops->table('kategori_genset')->insert(VoltraData::kategoriGenset());
        $ops->table('supplier')->insert(VoltraData::supplier());
        $ops->table('pelanggan')->insert(VoltraData::pelanggan());
        $ops->table('genset')->insert(VoltraData::genset());
        $ops->table('jadwal_ketersediaan')->insert(VoltraData::jadwalKetersediaan());
        $ops->table('transaksi_sewa')->insert(VoltraData::transaksiSewa());
        $ops->table('detail_sewa')->insert(VoltraData::detailSewa());
        $ops->table('pengembalian')->insert(VoltraData::pengembalian());
        $ops->table('pembayaran')->insert(VoltraData::pembayaran());
        $ops->table('suku_cadang')->insert(VoltraData::sukuCadang());

        // pemeliharaan — buang key 'status' (tidak ada kolomnya di DB).
        $pemeliharaan = array_map(function ($p) {
            unset($p['status']);
            return $p;
        }, VoltraData::pemeliharaan());
        $ops->table('pemeliharaan')->insert($pemeliharaan);
        $ops->table('detail_pemeliharaan')->insert(VoltraData::detailPemeliharaan());

        // penjualan_genset — minimal 3 unit yang sudah dilepas (historis).
        $ops->table('penjualan_genset')->insert([
            ['id_penjualan' => 1, 'id_perusahaan' => 1, 'id_genset' => 5, 'id_pengguna' => 3, 'tgl_jual' => '2026-02-20', 'harga_jual' => 250000000, 'nilai_buku_saat_jual' => 180000000, 'gain_loss' => 70000000, 'keterangan' => 'Penjualan unit CMN-500-0303 (laba)'],
            ['id_penjualan' => 2, 'id_perusahaan' => 1, 'id_genset' => 7, 'id_pengguna' => 3, 'tgl_jual' => '2026-03-05', 'harga_jual' => 120000000, 'nilai_buku_saat_jual' => 150000000, 'gain_loss' => -30000000, 'keterangan' => 'Pelepasan unit lama PKI-100-0059 (rugi)'],
            ['id_penjualan' => 3, 'id_perusahaan' => 1, 'id_genset' => 8, 'id_pengguna' => 1, 'tgl_jual' => '2026-03-28', 'harga_jual' => 300000000, 'nilai_buku_saat_jual' => 300000000, 'gain_loss' => 0, 'keterangan' => 'Tukar tambah unit PKI-250-0140 (impas)'],
        ]);

        // 5. Akuntansi.
        $acc->table('akun_perkiraan')->insert(VoltraData::akunPerkiraan());
        $acc->table('periode_akuntansi')->insert(VoltraData::periodeAkuntansi());
        $acc->table('jurnal_akuntansi')->insert(VoltraData::jurnalAkuntansi());

        // detail_jurnal — tambah id_perusahaan (NOT NULL + bagian FK komposit).
        $detailJurnal = array_map(fn ($d) => $d + ['id_perusahaan' => 1], VoltraData::detailJurnal());
        $acc->table('detail_jurnal')->insert($detailJurnal);

        // jadwal_penyusutan — periode_bulan kolom DATE; mock pakai 'Y-m' → lengkapi jadi tanggal-1.
        $penyusutan = array_map(function ($r) {
            $r['periode_bulan'] = $r['periode_bulan'] . '-01';
            return $r;
        }, VoltraData::jadwalPenyusutan());
        $acc->table('jadwal_penyusutan')->insert($penyusutan);

        $this->command->info('DummySeeder: data dummy terisi. Login: yeda@gmail.com / 12345678 (owner, PT Sinar Daya Nusantara).');
    }
}
