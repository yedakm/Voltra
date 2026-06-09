<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema `voltra_akuntansi` — domain AKUNTANSI (5 tabel).
 * Terhubung ke schema operasional lewat kolom id_perusahaan & id_genset
 * (polymorphic / cross-schema reference, tanpa foreign key fisik).
 */
return new class extends Migration
{
    protected string $conn = 'voltra_akuntansi';

    public function up(): void
    {
        // 17. akun_perkiraan (chart of accounts — composite PK)
        Schema::connection($this->conn)->create('akun_perkiraan', function (Blueprint $t) {
            $t->string('kode_akun', 20);
            $t->unsignedBigInteger('id_perusahaan');
            $t->string('nama_akun', 150);
            $t->enum('kategori_akun', ['aset', 'kewajiban', 'ekuitas', 'pendapatan', 'beban']);
            $t->string('sub_kategori', 100)->nullable();
            $t->enum('saldo_normal', ['debit', 'kredit']);
            $t->string('kode_parent', 20)->nullable();
            $t->boolean('is_aktif')->default(true);
            $t->primary(['kode_akun', 'id_perusahaan']);
        });

        // 18. periode_akuntansi
        Schema::connection($this->conn)->create('periode_akuntansi', function (Blueprint $t) {
            $t->id('id_periode');
            $t->unsignedBigInteger('id_perusahaan');
            $t->integer('tahun');
            $t->tinyInteger('bulan');
            $t->date('tgl_mulai')->nullable();
            $t->date('tgl_selesai')->nullable();
            $t->enum('status', ['aktif', 'ditutup'])->default('aktif');
            $t->dateTime('tgl_tutup_buku')->nullable();
            $t->unsignedBigInteger('ditutup_oleh')->nullable();
            $t->index(['id_perusahaan', 'tahun', 'bulan']);
        });

        // 19. jurnal_akuntansi (header — polymorphic reference)
        Schema::connection($this->conn)->create('jurnal_akuntansi', function (Blueprint $t) {
            $t->id('id_jurnal');
            $t->unsignedBigInteger('id_perusahaan');
            $t->foreignId('id_periode')->constrained('periode_akuntansi', 'id_periode');
            $t->string('no_bukti', 50)->nullable();
            $t->date('tanggal');
            $t->enum('jenis_jurnal', [
                'pembelian_aset', 'sewa', 'pembayaran', 'pemeliharaan', 'beban_operasional',
                'penyusutan', 'penjualan_aset', 'penyesuaian', 'penutup', 'manual',
            ]);
            $t->string('referensi_tipe', 50)->nullable();
            $t->unsignedBigInteger('referensi_id')->nullable();
            $t->decimal('total_debit', 15, 2)->default(0);
            $t->decimal('total_kredit', 15, 2)->default(0);
            $t->text('keterangan')->nullable();
            $t->unsignedBigInteger('dibuat_oleh')->nullable();
            $t->timestamp('dibuat_pada')->useCurrent();
        });

        // 20. detail_jurnal (baris debit/kredit)
        Schema::connection($this->conn)->create('detail_jurnal', function (Blueprint $t) {
            $t->id('id_detail_jurnal');
            $t->foreignId('id_jurnal')->constrained('jurnal_akuntansi', 'id_jurnal')->cascadeOnDelete();
            $t->string('kode_akun', 20);
            $t->unsignedBigInteger('id_perusahaan');
            $t->decimal('debit', 15, 2)->default(0);
            $t->decimal('kredit', 15, 2)->default(0);
            $t->text('keterangan')->nullable();
            $t->integer('urutan')->default(1);
            $t->foreign(['kode_akun', 'id_perusahaan'])
                ->references(['kode_akun', 'id_perusahaan'])->on('akun_perkiraan');
        });

        // 21. jadwal_penyusutan (riwayat depresiasi — snapshot)
        Schema::connection($this->conn)->create('jadwal_penyusutan', function (Blueprint $t) {
            $t->id('id_penyusutan');
            $t->unsignedBigInteger('id_genset');
            $t->unsignedBigInteger('id_perusahaan');
            $t->unsignedBigInteger('id_periode')->nullable();
            $t->unsignedBigInteger('id_jurnal')->nullable();
            $t->date('periode_bulan');
            $t->decimal('harga_perolehan', 15, 2);
            $t->decimal('nilai_residu', 15, 2);
            $t->integer('umur_ekonomis_bulan');
            $t->decimal('beban_penyusutan', 15, 2);
            $t->decimal('akumulasi_penyusutan', 15, 2)->default(0);
            $t->decimal('nilai_buku', 15, 2)->default(0);
            $t->enum('status_jurnal', ['pending', 'posted'])->default('pending');
            $t->index(['id_genset', 'periode_bulan']);
        });
    }

    public function down(): void
    {
        foreach (['jadwal_penyusutan', 'detail_jurnal', 'jurnal_akuntansi', 'periode_akuntansi', 'akun_perkiraan'] as $table) {
            Schema::connection($this->conn)->dropIfExists($table);
        }
    }
};
