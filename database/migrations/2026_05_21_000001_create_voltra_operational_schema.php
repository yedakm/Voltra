<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema `voltra` — domain OPERASIONAL (16 tabel).
 * Tabel dibuat berurutan sesuai dependensi foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. perusahaan (tenant master)
        Schema::create('perusahaan', function (Blueprint $t) {
            $t->id('id_perusahaan');
            $t->string('nama_perusahaan', 150);
            $t->string('logo', 255)->nullable();
            $t->text('alamat')->nullable();
            $t->string('no_telepon', 20)->nullable();
            $t->string('email', 100)->nullable();
            $t->string('npwp', 30)->nullable();
            $t->date('tgl_bergabung')->nullable();
            $t->boolean('status_aktif')->default(true);
        });

        // 2. merek (master global — tidak terikat perusahaan)
        Schema::create('merek', function (Blueprint $t) {
            $t->id('id_merek');
            $t->string('nama_merek', 100);
            $t->string('negara_asal', 100)->nullable();
            $t->text('keterangan')->nullable();
        });

        // 3. pengguna
        Schema::create('pengguna', function (Blueprint $t) {
            $t->id('id_pengguna');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->string('nama', 100);
            $t->string('email', 100);
            $t->string('password', 255);
            $t->enum('role', ['admin', 'operator', 'teknisi', 'akuntan', 'owner']);
            $t->string('avatar', 5)->nullable();
            $t->rememberToken();
            $t->unique(['email', 'id_perusahaan']);
        });

        // 4. kategori_genset
        Schema::create('kategori_genset', function (Blueprint $t) {
            $t->id('id_kategori');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->string('kapasitas', 50);
            $t->integer('umur_ekonomis_default');
            $t->decimal('estimasi_nilai_residu', 15, 2)->default(0);
        });

        // 5. supplier
        Schema::create('supplier', function (Blueprint $t) {
            $t->id('id_supplier');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->string('nama_supplier', 150);
            $t->string('pic_kontak', 100)->nullable();
            $t->string('no_telepon', 20)->nullable();
            $t->string('email', 100)->nullable();
            $t->text('alamat')->nullable();
        });

        // 6. pelanggan
        Schema::create('pelanggan', function (Blueprint $t) {
            $t->id('id_pelanggan');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->string('nama_perusahaan', 150);
            $t->string('pic_kontak', 100)->nullable();
            $t->text('alamat_lengkap')->nullable();
            $t->string('npwp', 30)->nullable();
            $t->string('no_telepon', 20)->nullable();
            $t->string('email', 100)->nullable();
        });

        // 7. genset
        Schema::create('genset', function (Blueprint $t) {
            $t->id('id_genset');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->foreignId('id_kategori')->nullable()->constrained('kategori_genset', 'id_kategori')->nullOnDelete();
            $t->foreignId('id_merek')->nullable()->constrained('merek', 'id_merek')->nullOnDelete();
            $t->foreignId('id_supplier')->nullable()->constrained('supplier', 'id_supplier')->nullOnDelete();
            $t->string('nomor_seri', 100);
            $t->date('tgl_perolehan');
            $t->decimal('harga_perolehan', 15, 2);
            $t->decimal('nilai_residu_aktual', 15, 2)->default(0);
            $t->integer('umur_ekonomis_aktual')->default(96);
            $t->enum('status', ['di_perusahaan', 'di_proyek', 'di_gudang', 'terjual', 'rusak'])->default('di_gudang');
            $t->text('deskripsi')->nullable();
            $t->string('foto', 255)->nullable();
            $t->text('lokasi_terkini')->nullable();
        });

        // 8. jadwal_ketersediaan (materialized cache)
        Schema::create('jadwal_ketersediaan', function (Blueprint $t) {
            $t->id('id_jadwal');
            $t->foreignId('id_genset')->constrained('genset', 'id_genset')->cascadeOnDelete();
            $t->date('tanggal');
            $t->enum('status', ['tersedia', 'disewa', 'maintenance', 'tidak_tersedia'])->default('tersedia');
            $t->index(['id_genset', 'tanggal']);
        });

        // 9. transaksi_sewa (header sewa + invoice terpadu)
        Schema::create('transaksi_sewa', function (Blueprint $t) {
            $t->id('id_sewa');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->foreignId('id_pelanggan')->constrained('pelanggan', 'id_pelanggan');
            $t->foreignId('id_pengguna')->constrained('pengguna', 'id_pengguna');
            $t->string('no_referensi_kontrak', 50)->nullable();
            $t->string('no_invoice', 50)->nullable();
            $t->date('tgl_pemesanan');
            $t->date('tgl_terbit_invoice')->nullable();
            $t->date('tgl_jatuh_tempo')->nullable();
            $t->decimal('total_tagihan', 15, 2)->default(0);
            $t->decimal('pajak', 15, 2)->default(0);
            $t->enum('status_pesanan', ['pesan', 'deal', 'dibatalkan', 'selesai'])->default('pesan');
            $t->enum('status_pembayaran', ['belum_bayar', 'dp', 'lunas', 'overdue'])->default('belum_bayar');
            $t->text('keterangan')->nullable();
        });

        // 10. detail_sewa (composite PK)
        Schema::create('detail_sewa', function (Blueprint $t) {
            $t->foreignId('id_sewa')->constrained('transaksi_sewa', 'id_sewa')->cascadeOnDelete();
            $t->foreignId('id_genset')->constrained('genset', 'id_genset');
            $t->date('start_date');
            $t->date('end_date');
            $t->text('alamat_proyek')->nullable();
            $t->decimal('harga_sewa_unit', 15, 2)->default(0);
            $t->decimal('biaya_operator', 15, 2)->default(0);
            $t->decimal('biaya_mobdemob', 15, 2)->default(0);
            $t->decimal('biaya_bbm', 15, 2)->default(0);
            $t->primary(['id_sewa', 'id_genset']);
        });

        // 11. pengembalian (serah-terima)
        Schema::create('pengembalian', function (Blueprint $t) {
            $t->id('id_pengembalian');
            $t->unsignedBigInteger('id_sewa');
            $t->unsignedBigInteger('id_genset');
            $t->enum('jenis_aktivitas', ['pengambilan', 'pengembalian']);
            $t->dateTime('tanggal');
            $t->string('pic_dari_pelanggan', 100)->nullable();
            $t->string('pic_dari_rental', 100)->nullable();
            $t->text('kondisi_genset')->nullable();
            $t->string('foto_kondisi', 255)->nullable();
            $t->unsignedBigInteger('dicatat_oleh')->nullable();
            $t->text('catatan')->nullable();
            $t->index(['id_sewa', 'id_genset']);
        });

        // 12. pembayaran
        Schema::create('pembayaran', function (Blueprint $t) {
            $t->id('id_pembayaran');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->foreignId('id_sewa')->constrained('transaksi_sewa', 'id_sewa')->cascadeOnDelete();
            $t->string('no_kuitansi', 50)->nullable();
            $t->date('tgl_bayar');
            $t->decimal('nominal_bayar', 15, 2);
            $t->enum('metode_bayar', ['transfer', 'tunai', 'giro', 'kartu_kredit'])->default('transfer');
            $t->text('keterangan')->nullable();
        });

        // 13. suku_cadang
        Schema::create('suku_cadang', function (Blueprint $t) {
            $t->id('id_part');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->string('nama_part', 150);
            $t->string('kode_sku', 50);
            $t->integer('stok_tersedia')->default(0);
            $t->decimal('harga_satuan', 15, 2)->default(0);
            $t->unique(['kode_sku', 'id_perusahaan']);
        });

        // 14. pemeliharaan
        Schema::create('pemeliharaan', function (Blueprint $t) {
            $t->id('id_pemeliharaan');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->foreignId('id_genset')->constrained('genset', 'id_genset');
            $t->foreignId('id_pengguna')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $t->date('tgl_mulai_servis');
            $t->date('tgl_selesai')->nullable();
            $t->enum('jenis_servis', ['rutin', 'perbaikan', 'overhaul']);
            $t->decimal('biaya_jasa_eksternal', 15, 2)->default(0);
            $t->text('keterangan')->nullable();
        });

        // 15. detail_pemeliharaan (composite PK)
        Schema::create('detail_pemeliharaan', function (Blueprint $t) {
            $t->foreignId('id_pemeliharaan')->constrained('pemeliharaan', 'id_pemeliharaan')->cascadeOnDelete();
            $t->foreignId('id_part')->constrained('suku_cadang', 'id_part');
            $t->integer('qty_digunakan');
            $t->decimal('subtotal_harga_part', 15, 2);
            $t->primary(['id_pemeliharaan', 'id_part']);
        });

        // 16. penjualan_genset (pelepasan aset)
        Schema::create('penjualan_genset', function (Blueprint $t) {
            $t->id('id_penjualan');
            $t->foreignId('id_perusahaan')->constrained('perusahaan', 'id_perusahaan')->cascadeOnDelete();
            $t->unsignedBigInteger('id_genset');
            $t->unsignedBigInteger('id_pengguna')->nullable();
            $t->date('tgl_jual');
            $t->decimal('harga_jual', 15, 2);
            $t->decimal('nilai_buku_saat_jual', 15, 2);
            $t->decimal('gain_loss', 15, 2)->default(0);
            $t->text('keterangan')->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'penjualan_genset', 'detail_pemeliharaan', 'pemeliharaan', 'suku_cadang',
            'pembayaran', 'pengembalian', 'detail_sewa', 'transaksi_sewa',
            'jadwal_ketersediaan', 'genset', 'pelanggan', 'supplier',
            'kategori_genset', 'pengguna', 'merek', 'perusahaan',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
