<?php

namespace App\Support;

/**
 * Voltra ERP — frontend mock data.
 *
 * Ported 1:1 from the Claude Design prototype (src/data.jsx). Field names follow
 * the PRD schema exactly so each array maps directly onto an Eloquent model when
 * the real database integration is wired in later.
 */
class VoltraData
{
    /** @var array<string,mixed>|null Memoized full dataset. */
    protected static ?array $cache = null;

    /* =========================================================
     | Schema: voltra (Operational)
     |=========================================================*/

    public static function perusahaan(): array
    {
        return [
            ['id_perusahaan' => 1, 'nama_perusahaan' => 'PT Sinar Daya Nusantara', 'logo' => 'SDN', 'alamat' => 'Jl. Industri Raya No.12, Jakarta', 'no_telepon' => '021-5551200', 'email' => 'admin@sdn.co.id', 'npwp' => '01.234.567.8-001.000', 'tgl_bergabung' => '2025-01-15', 'status_aktif' => 1],
            ['id_perusahaan' => 2, 'nama_perusahaan' => 'CV Multi Genset Borneo', 'logo' => 'MGB', 'alamat' => 'Balikpapan, Kaltim', 'no_telepon' => '0542-555088', 'email' => 'ops@mgb.id', 'npwp' => '02.345.678.9-002.000', 'tgl_bergabung' => '2025-06-10', 'status_aktif' => 1],
        ];
    }

    public static function pengguna(): array
    {
        return [
            ['id_pengguna' => 1, 'id_perusahaan' => 1, 'nama' => 'Andi Pratama', 'email' => 'andi@voltra.id', 'role' => 'admin', 'avatar' => 'AP'],
            ['id_pengguna' => 2, 'id_perusahaan' => 1, 'nama' => 'Bima Setiawan', 'email' => 'bima@voltra.id', 'role' => 'teknisi', 'avatar' => 'BS'],
            ['id_pengguna' => 3, 'id_perusahaan' => 1, 'nama' => 'Citra Wulandari', 'email' => 'citra@voltra.id', 'role' => 'owner', 'avatar' => 'CW'],
            ['id_pengguna' => 4, 'id_perusahaan' => 1, 'nama' => 'Dedi Kurniawan', 'email' => 'dedi@voltra.id', 'role' => 'teknisi', 'avatar' => 'DK'],
            ['id_pengguna' => 5, 'id_perusahaan' => 1, 'nama' => 'Eka Sari', 'email' => 'eka@voltra.id', 'role' => 'akuntan', 'avatar' => 'ES'],
            ['id_pengguna' => 6, 'id_perusahaan' => 1, 'nama' => 'Faisal Rahman', 'email' => 'faisal@voltra.id', 'role' => 'operator', 'avatar' => 'FR'],
        ];
    }

    public static function merek(): array
    {
        return [
            ['id_merek' => 1, 'nama_merek' => 'Cummins', 'negara_asal' => 'USA', 'keterangan' => 'Heavy-duty diesel genset'],
            ['id_merek' => 2, 'nama_merek' => 'Perkins', 'negara_asal' => 'UK', 'keterangan' => 'Industrial & rental grade'],
            ['id_merek' => 3, 'nama_merek' => 'Caterpillar', 'negara_asal' => 'USA', 'keterangan' => 'High capacity diesel'],
            ['id_merek' => 4, 'nama_merek' => 'Mitsubishi', 'negara_asal' => 'Japan', 'keterangan' => 'Compact unit'],
            ['id_merek' => 5, 'nama_merek' => 'Volvo Penta', 'negara_asal' => 'Sweden', 'keterangan' => 'Marine & industrial'],
        ];
    }

    public static function pelanggan(): array
    {
        return [
            ['id_pelanggan' => 1, 'id_perusahaan' => 1, 'nama_perusahaan' => 'PT Adhi Konstruksi', 'pic_kontak' => 'Pak Hartono', 'alamat_lengkap' => 'Jl. MH Thamrin No.5, Jakpus', 'npwp' => '01.234.567.8-001.000', 'no_telepon' => '021-5550101', 'email' => 'proc@adhi.co.id'],
            ['id_pelanggan' => 2, 'id_perusahaan' => 1, 'nama_perusahaan' => 'CV Mitra Event', 'pic_kontak' => 'Ibu Lestari', 'alamat_lengkap' => 'Jl. Kemang Raya No.22, Jaksel', 'npwp' => '02.345.678.9-002.000', 'no_telepon' => '021-5550202', 'email' => 'sales@mitraevent.id'],
            ['id_pelanggan' => 3, 'id_perusahaan' => 1, 'nama_perusahaan' => 'PT Borneo Mining', 'pic_kontak' => 'Bpk. Rizki', 'alamat_lengkap' => 'Site Tenggarong, Kaltim', 'npwp' => '03.456.789.0-003.000', 'no_telepon' => '0541-555030', 'email' => 'logistik@borneomining.com'],
            ['id_pelanggan' => 4, 'id_perusahaan' => 1, 'nama_perusahaan' => 'PT Sahabat Properti', 'pic_kontak' => 'Ibu Yanti', 'alamat_lengkap' => 'BSD City, Tangerang', 'npwp' => '04.567.890.1-004.000', 'no_telepon' => '021-5550404', 'email' => 'pm@sahabatproperti.com'],
            ['id_pelanggan' => 5, 'id_perusahaan' => 1, 'nama_perusahaan' => 'Hotel Grand Melati', 'pic_kontak' => 'Mgr. Operasi', 'alamat_lengkap' => 'Jl. Sudirman No.88, Jakpus', 'npwp' => '05.678.901.2-005.000', 'no_telepon' => '021-5550505', 'email' => 'gm@grandmelati.com'],
        ];
    }

    public static function supplier(): array
    {
        return [
            ['id_supplier' => 1, 'id_perusahaan' => 1, 'nama_supplier' => 'PT Cummins Sales IDN', 'pic_kontak' => 'Arif', 'no_telepon' => '021-8889001', 'email' => 'sales@cummins.id', 'alamat' => 'Cikarang Barat'],
            ['id_supplier' => 2, 'id_perusahaan' => 1, 'nama_supplier' => 'PT Perkasa Diesel', 'pic_kontak' => 'Hendra', 'no_telepon' => '021-8889002', 'email' => 'info@perkasa.id', 'alamat' => 'Bekasi Utara'],
            ['id_supplier' => 3, 'id_perusahaan' => 1, 'nama_supplier' => 'CV Sparepart Jaya', 'pic_kontak' => 'Wati', 'no_telepon' => '021-8889003', 'email' => 'order@spjjaya.id', 'alamat' => 'Kelapa Gading'],
        ];
    }

    public static function kategoriGenset(): array
    {
        return [
            ['id_kategori' => 1, 'id_perusahaan' => 1, 'kapasitas' => '100 kVA', 'umur_ekonomis_default' => 96, 'estimasi_nilai_residu' => 30000000],
            ['id_kategori' => 2, 'id_perusahaan' => 1, 'kapasitas' => '250 kVA', 'umur_ekonomis_default' => 96, 'estimasi_nilai_residu' => 60000000],
            ['id_kategori' => 3, 'id_perusahaan' => 1, 'kapasitas' => '500 kVA', 'umur_ekonomis_default' => 120, 'estimasi_nilai_residu' => 110000000],
            ['id_kategori' => 4, 'id_perusahaan' => 1, 'kapasitas' => '1000 kVA', 'umur_ekonomis_default' => 120, 'estimasi_nilai_residu' => 220000000],
        ];
    }

    public static function genset(): array
    {
        return [
            ['id_genset' => 1, 'id_perusahaan' => 1, 'id_kategori' => 2, 'id_merek' => 1, 'id_supplier' => 1, 'nomor_seri' => 'CMN-250-0231', 'tgl_perolehan' => '2023-02-14', 'harga_perolehan' => 480000000, 'status' => 'di_proyek', 'nilai_residu_aktual' => 60000000, 'umur_ekonomis_aktual' => 96, 'deskripsi' => 'Genset diesel silenced canopy', 'foto' => null, 'lokasi_terkini' => 'Proyek MRT Fase 2, Thamrin – Jakarta Pusat'],
            ['id_genset' => 2, 'id_perusahaan' => 1, 'id_kategori' => 2, 'id_merek' => 1, 'id_supplier' => 1, 'nomor_seri' => 'CMN-250-0232', 'tgl_perolehan' => '2023-02-14', 'harga_perolehan' => 480000000, 'status' => 'di_gudang', 'nilai_residu_aktual' => 60000000, 'umur_ekonomis_aktual' => 96, 'deskripsi' => 'Genset diesel silenced canopy', 'foto' => null, 'lokasi_terkini' => 'Gudang utama – Cakung'],
            ['id_genset' => 3, 'id_perusahaan' => 1, 'id_kategori' => 3, 'id_merek' => 2, 'id_supplier' => 2, 'nomor_seri' => 'PKI-500-0117', 'tgl_perolehan' => '2022-07-30', 'harga_perolehan' => 920000000, 'status' => 'di_proyek', 'nilai_residu_aktual' => 110000000, 'umur_ekonomis_aktual' => 120, 'deskripsi' => 'Heavy duty open frame', 'foto' => null, 'lokasi_terkini' => 'Site Tenggarong Block-C – Kaltim'],
            ['id_genset' => 4, 'id_perusahaan' => 1, 'id_kategori' => 1, 'id_merek' => 2, 'id_supplier' => 2, 'nomor_seri' => 'PKI-100-0058', 'tgl_perolehan' => '2024-05-02', 'harga_perolehan' => 265000000, 'status' => 'di_gudang', 'nilai_residu_aktual' => 30000000, 'umur_ekonomis_aktual' => 96, 'deskripsi' => 'Soundproof rental unit', 'foto' => null, 'lokasi_terkini' => 'Gudang utama – Cakung'],
            ['id_genset' => 5, 'id_perusahaan' => 1, 'id_kategori' => 3, 'id_merek' => 1, 'id_supplier' => 1, 'nomor_seri' => 'CMN-500-0303', 'tgl_perolehan' => '2021-11-11', 'harga_perolehan' => 880000000, 'status' => 'rusak', 'nilai_residu_aktual' => 100000000, 'umur_ekonomis_aktual' => 120, 'deskripsi' => 'Overhaul jadwal Q2', 'foto' => null, 'lokasi_terkini' => 'Workshop – Cakung'],
            ['id_genset' => 6, 'id_perusahaan' => 1, 'id_kategori' => 4, 'id_merek' => 1, 'id_supplier' => 1, 'nomor_seri' => 'CMN-1K-0012', 'tgl_perolehan' => '2020-03-20', 'harga_perolehan' => 1850000000, 'status' => 'di_proyek', 'nilai_residu_aktual' => 220000000, 'umur_ekonomis_aktual' => 120, 'deskripsi' => 'Containerized 1000kVA', 'foto' => null, 'lokasi_terkini' => 'Ballroom Hotel Grand Melati – Sudirman'],
            ['id_genset' => 7, 'id_perusahaan' => 1, 'id_kategori' => 1, 'id_merek' => 2, 'id_supplier' => 2, 'nomor_seri' => 'PKI-100-0059', 'tgl_perolehan' => '2024-05-02', 'harga_perolehan' => 265000000, 'status' => 'di_gudang', 'nilai_residu_aktual' => 30000000, 'umur_ekonomis_aktual' => 96, 'deskripsi' => 'Soundproof rental unit', 'foto' => null, 'lokasi_terkini' => 'Gudang utama – Cakung'],
            ['id_genset' => 8, 'id_perusahaan' => 1, 'id_kategori' => 2, 'id_merek' => 2, 'id_supplier' => 2, 'nomor_seri' => 'PKI-250-0140', 'tgl_perolehan' => '2023-08-08', 'harga_perolehan' => 470000000, 'status' => 'di_gudang', 'nilai_residu_aktual' => 58000000, 'umur_ekonomis_aktual' => 96, 'deskripsi' => 'Rental fleet 250kVA', 'foto' => null, 'lokasi_terkini' => 'Gudang utama – Cakung'],
        ];
    }

    public static function transaksiSewa(): array
    {
        return [
            ['id_sewa' => 1001, 'id_perusahaan' => 1, 'id_pelanggan' => 1, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-041', 'no_invoice' => 'INV/2026/04/001', 'tgl_pemesanan' => '2026-04-01', 'tgl_terbit_invoice' => '2026-04-05', 'tgl_jatuh_tempo' => '2026-04-20', 'total_tagihan' => 75000000, 'pajak' => 8250000, 'status_pesanan' => 'deal', 'status_pembayaran' => 'lunas', 'keterangan' => 'Sewa periode April'],
            ['id_sewa' => 1002, 'id_perusahaan' => 1, 'id_pelanggan' => 3, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-042', 'no_invoice' => 'INV/2026/04/002', 'tgl_pemesanan' => '2026-04-03', 'tgl_terbit_invoice' => '2026-04-08', 'tgl_jatuh_tempo' => '2026-04-23', 'total_tagihan' => 188500000, 'pajak' => 20735000, 'status_pesanan' => 'deal', 'status_pembayaran' => 'dp', 'keterangan' => 'DP 50% via giro'],
            ['id_sewa' => 1003, 'id_perusahaan' => 1, 'id_pelanggan' => 2, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-043', 'no_invoice' => 'INV/2026/04/003', 'tgl_pemesanan' => '2026-04-10', 'tgl_terbit_invoice' => '2026-04-22', 'tgl_jatuh_tempo' => '2026-05-07', 'total_tagihan' => 5800000, 'pajak' => 638000, 'status_pesanan' => 'pesan', 'status_pembayaran' => 'belum_bayar', 'keterangan' => 'Event wedding 3 hari'],
            ['id_sewa' => 1004, 'id_perusahaan' => 1, 'id_pelanggan' => 5, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-044', 'no_invoice' => 'INV/2026/04/004', 'tgl_pemesanan' => '2026-04-12', 'tgl_terbit_invoice' => '2026-04-15', 'tgl_jatuh_tempo' => '2026-04-30', 'total_tagihan' => 113600000, 'pajak' => 12496000, 'status_pesanan' => 'deal', 'status_pembayaran' => 'lunas', 'keterangan' => 'Sewa ballroom'],
            ['id_sewa' => 1005, 'id_perusahaan' => 1, 'id_pelanggan' => 4, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-040', 'no_invoice' => 'INV/2026/03/012', 'tgl_pemesanan' => '2026-03-18', 'tgl_terbit_invoice' => '2026-03-20', 'tgl_jatuh_tempo' => '2026-04-04', 'total_tagihan' => 30150000, 'pajak' => 3316500, 'status_pesanan' => 'selesai', 'status_pembayaran' => 'lunas', 'keterangan' => 'BSD Sky Tower'],
            ['id_sewa' => 1006, 'id_perusahaan' => 1, 'id_pelanggan' => 1, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-045', 'no_invoice' => 'INV/2026/04/005', 'tgl_pemesanan' => '2026-04-18', 'tgl_terbit_invoice' => '2026-04-18', 'tgl_jatuh_tempo' => '2026-05-03', 'total_tagihan' => 41000000, 'pajak' => 4510000, 'status_pesanan' => 'pesan', 'status_pembayaran' => 'belum_bayar', 'keterangan' => 'Lanjutan proyek MRT'],
            ['id_sewa' => 1007, 'id_perusahaan' => 1, 'id_pelanggan' => 2, 'id_pengguna' => 1, 'no_referensi_kontrak' => 'KTR-2026-039', 'no_invoice' => 'INV/2026/03/008', 'tgl_pemesanan' => '2026-03-01', 'tgl_terbit_invoice' => '2026-03-02', 'tgl_jatuh_tempo' => '2026-03-17', 'total_tagihan' => 8200000, 'pajak' => 902000, 'status_pesanan' => 'dibatalkan', 'status_pembayaran' => 'belum_bayar', 'keterangan' => 'Dibatalkan karena unit double booking'],
        ];
    }

    public static function detailSewa(): array
    {
        return [
            ['id_sewa' => 1001, 'id_genset' => 1, 'start_date' => '2026-04-05', 'end_date' => '2026-04-30', 'alamat_proyek' => 'Proyek MRT Fase 2, Thamrin', 'harga_sewa_unit' => 2500000, 'biaya_operator' => 350000, 'biaya_mobdemob' => 4500000, 'biaya_bbm' => 1200000],
            ['id_sewa' => 1002, 'id_genset' => 3, 'start_date' => '2026-04-08', 'end_date' => '2026-05-08', 'alamat_proyek' => 'Site Tenggarong Block-C', 'harga_sewa_unit' => 4800000, 'biaya_operator' => 450000, 'biaya_mobdemob' => 12000000, 'biaya_bbm' => 3500000],
            ['id_sewa' => 1003, 'id_genset' => 4, 'start_date' => '2026-04-22', 'end_date' => '2026-04-24', 'alamat_proyek' => 'Event Wedding Kemang', 'harga_sewa_unit' => 1500000, 'biaya_operator' => 300000, 'biaya_mobdemob' => 1500000, 'biaya_bbm' => 500000],
            ['id_sewa' => 1004, 'id_genset' => 6, 'start_date' => '2026-04-15', 'end_date' => '2026-04-29', 'alamat_proyek' => 'Ballroom Hotel Grand Melati', 'harga_sewa_unit' => 7500000, 'biaya_operator' => 500000, 'biaya_mobdemob' => 6000000, 'biaya_bbm' => 2100000],
            ['id_sewa' => 1005, 'id_genset' => 2, 'start_date' => '2026-03-20', 'end_date' => '2026-03-30', 'alamat_proyek' => 'BSD Sky Tower', 'harga_sewa_unit' => 2500000, 'biaya_operator' => 350000, 'biaya_mobdemob' => 4000000, 'biaya_bbm' => 900000],
            ['id_sewa' => 1006, 'id_genset' => 8, 'start_date' => '2026-04-28', 'end_date' => '2026-05-12', 'alamat_proyek' => 'Proyek MRT Fase 2, Dukuh Atas', 'harga_sewa_unit' => 2500000, 'biaya_operator' => 350000, 'biaya_mobdemob' => 4500000, 'biaya_bbm' => 1000000],
        ];
    }

    public static function pengembalian(): array
    {
        return [
            ['id_pengembalian' => 1, 'id_sewa' => 1001, 'id_genset' => 1, 'jenis_aktivitas' => 'pengambilan', 'tanggal' => '2026-04-05 08:30', 'pic_dari_pelanggan' => 'Bpk. Hartono', 'pic_dari_rental' => 'Faisal Rahman', 'kondisi_genset' => 'Unit prima, semua indikator normal, BBM penuh.', 'foto_kondisi' => 'foto_h001.jpg', 'dicatat_oleh' => 6, 'catatan' => 'Pemasangan di lantai dasar gedung MRT'],
            ['id_pengembalian' => 2, 'id_sewa' => 1002, 'id_genset' => 3, 'jenis_aktivitas' => 'pengambilan', 'tanggal' => '2026-04-08 06:00', 'pic_dari_pelanggan' => 'Bpk. Rizki', 'pic_dari_rental' => 'Bima Setiawan', 'kondisi_genset' => 'Cat sedikit baret samping kiri, fungsi normal.', 'foto_kondisi' => 'foto_h002.jpg', 'dicatat_oleh' => 1, 'catatan' => 'Kirim via truk angkutan, ETA 2 hari'],
            ['id_pengembalian' => 3, 'id_sewa' => 1004, 'id_genset' => 6, 'jenis_aktivitas' => 'pengambilan', 'tanggal' => '2026-04-15 14:00', 'pic_dari_pelanggan' => 'Mgr. Operasi', 'pic_dari_rental' => 'Faisal Rahman', 'kondisi_genset' => 'Unit baru servis rutin, kondisi excellent.', 'foto_kondisi' => 'foto_h003.jpg', 'dicatat_oleh' => 6, 'catatan' => ''],
            ['id_pengembalian' => 4, 'id_sewa' => 1005, 'id_genset' => 2, 'jenis_aktivitas' => 'pengambilan', 'tanggal' => '2026-03-20 07:00', 'pic_dari_pelanggan' => 'Ibu Yanti', 'pic_dari_rental' => 'Faisal Rahman', 'kondisi_genset' => 'Kondisi baik.', 'foto_kondisi' => 'foto_h004.jpg', 'dicatat_oleh' => 1, 'catatan' => ''],
            ['id_pengembalian' => 5, 'id_sewa' => 1005, 'id_genset' => 2, 'jenis_aktivitas' => 'pengembalian', 'tanggal' => '2026-03-30 16:30', 'pic_dari_pelanggan' => 'Ibu Yanti', 'pic_dari_rental' => 'Bima Setiawan', 'kondisi_genset' => 'Body baret minor, oli perlu diganti, BBM 1/4 tangki.', 'foto_kondisi' => 'foto_h005.jpg', 'dicatat_oleh' => 2, 'catatan' => 'Perlu servis rutin sebelum unit re-deploy'],
        ];
    }

    public static function pembayaran(): array
    {
        return [
            ['id_pembayaran' => 1, 'id_perusahaan' => 1, 'id_sewa' => 1001, 'no_kuitansi' => 'KWT-26040-001', 'tgl_bayar' => '2026-04-12', 'nominal_bayar' => 83250000, 'metode_bayar' => 'transfer', 'keterangan' => 'Pembayaran penuh'],
            ['id_pembayaran' => 2, 'id_perusahaan' => 1, 'id_sewa' => 1002, 'no_kuitansi' => 'KWT-26040-002', 'tgl_bayar' => '2026-04-15', 'nominal_bayar' => 100000000, 'metode_bayar' => 'giro', 'keterangan' => 'DP 50%'],
            ['id_pembayaran' => 3, 'id_perusahaan' => 1, 'id_sewa' => 1004, 'no_kuitansi' => 'KWT-26040-003', 'tgl_bayar' => '2026-04-18', 'nominal_bayar' => 126096000, 'metode_bayar' => 'transfer', 'keterangan' => 'Pembayaran penuh'],
            ['id_pembayaran' => 4, 'id_perusahaan' => 1, 'id_sewa' => 1005, 'no_kuitansi' => 'KWT-26030-010', 'tgl_bayar' => '2026-04-01', 'nominal_bayar' => 33466500, 'metode_bayar' => 'giro', 'keterangan' => 'Pelunasan'],
        ];
    }

    public static function sukuCadang(): array
    {
        return [
            ['id_part' => 1, 'id_perusahaan' => 1, 'nama_part' => 'Oli Mesin SAE 15W-40 (20L)', 'kode_sku' => 'OLI-1540-20', 'stok_tersedia' => 42, 'harga_satuan' => 1250000],
            ['id_part' => 2, 'id_perusahaan' => 1, 'nama_part' => 'Filter Oli Cummins', 'kode_sku' => 'FLT-OIL-CMN', 'stok_tersedia' => 18, 'harga_satuan' => 425000],
            ['id_part' => 3, 'id_perusahaan' => 1, 'nama_part' => 'Filter Bahan Bakar', 'kode_sku' => 'FLT-FUEL-01', 'stok_tersedia' => 8, 'harga_satuan' => 385000],
            ['id_part' => 4, 'id_perusahaan' => 1, 'nama_part' => 'Filter Udara', 'kode_sku' => 'FLT-AIR-02', 'stok_tersedia' => 24, 'harga_satuan' => 540000],
            ['id_part' => 5, 'id_perusahaan' => 1, 'nama_part' => 'Aki Kering 100Ah', 'kode_sku' => 'AKI-100AH', 'stok_tersedia' => 3, 'harga_satuan' => 1850000],
            ['id_part' => 6, 'id_perusahaan' => 1, 'nama_part' => 'Radiator Coolant (5L)', 'kode_sku' => 'COL-RAD-5', 'stok_tersedia' => 15, 'harga_satuan' => 285000],
            ['id_part' => 7, 'id_perusahaan' => 1, 'nama_part' => 'V-Belt Alternator', 'kode_sku' => 'BLT-ALT-11', 'stok_tersedia' => 11, 'harga_satuan' => 345000],
            ['id_part' => 8, 'id_perusahaan' => 1, 'nama_part' => 'Busi / Glow Plug', 'kode_sku' => 'PLG-GLW-03', 'stok_tersedia' => 2, 'harga_satuan' => 225000],
        ];
    }

    public static function pemeliharaan(): array
    {
        return [
            ['id_pemeliharaan' => 1, 'id_perusahaan' => 1, 'id_genset' => 5, 'id_pengguna' => 2, 'tgl_mulai_servis' => '2026-04-10', 'tgl_selesai' => null, 'jenis_servis' => 'overhaul', 'biaya_jasa_eksternal' => 12500000, 'keterangan' => 'Turun mesin – piston ring', 'status' => 'Dalam Proses'],
            ['id_pemeliharaan' => 2, 'id_perusahaan' => 1, 'id_genset' => 2, 'id_pengguna' => 2, 'tgl_mulai_servis' => '2026-04-01', 'tgl_selesai' => '2026-04-02', 'jenis_servis' => 'rutin', 'biaya_jasa_eksternal' => 0, 'keterangan' => 'Ganti oli & filter berkala', 'status' => 'Selesai'],
            ['id_pemeliharaan' => 3, 'id_perusahaan' => 1, 'id_genset' => 4, 'id_pengguna' => 4, 'tgl_mulai_servis' => '2026-03-28', 'tgl_selesai' => '2026-03-29', 'jenis_servis' => 'rutin', 'biaya_jasa_eksternal' => 0, 'keterangan' => 'Servis 250 jam', 'status' => 'Selesai'],
            ['id_pemeliharaan' => 4, 'id_perusahaan' => 1, 'id_genset' => 7, 'id_pengguna' => 4, 'tgl_mulai_servis' => '2026-04-20', 'tgl_selesai' => null, 'jenis_servis' => 'perbaikan', 'biaya_jasa_eksternal' => 750000, 'keterangan' => 'Perbaikan starter motor', 'status' => 'Dalam Proses'],
        ];
    }

    public static function detailPemeliharaan(): array
    {
        return [
            ['id_pemeliharaan' => 1, 'id_part' => 1, 'qty_digunakan' => 2, 'subtotal_harga_part' => 2500000],
            ['id_pemeliharaan' => 1, 'id_part' => 2, 'qty_digunakan' => 2, 'subtotal_harga_part' => 850000],
            ['id_pemeliharaan' => 1, 'id_part' => 3, 'qty_digunakan' => 2, 'subtotal_harga_part' => 770000],
            ['id_pemeliharaan' => 2, 'id_part' => 1, 'qty_digunakan' => 1, 'subtotal_harga_part' => 1250000],
            ['id_pemeliharaan' => 2, 'id_part' => 2, 'qty_digunakan' => 1, 'subtotal_harga_part' => 425000],
            ['id_pemeliharaan' => 3, 'id_part' => 4, 'qty_digunakan' => 1, 'subtotal_harga_part' => 540000],
            ['id_pemeliharaan' => 4, 'id_part' => 1, 'qty_digunakan' => 1, 'subtotal_harga_part' => 1250000],
            ['id_pemeliharaan' => 4, 'id_part' => 6, 'qty_digunakan' => 2, 'subtotal_harga_part' => 570000],
        ];
    }

    public static function penjualanGenset(): array
    {
        return [
            ['id_penjualan' => 1, 'id_perusahaan' => 1, 'id_genset' => 99, 'id_pengguna' => 1, 'tgl_jual' => '2026-02-20', 'harga_jual' => 250000000, 'nilai_buku_saat_jual' => 180000000, 'gain_loss' => 70000000, 'keterangan' => 'Unit lama CMN-250-0077, laba penjualan'],
        ];
    }

    /* =========================================================
     | Schema: voltra_akuntansi (Accounting)
     |=========================================================*/

    public static function periodeAkuntansi(): array
    {
        return [
            ['id_periode' => 1, 'id_perusahaan' => 1, 'tahun' => 2026, 'bulan' => 1, 'tgl_mulai' => '2026-01-01', 'tgl_selesai' => '2026-01-31', 'status' => 'ditutup', 'tgl_tutup_buku' => '2026-02-03', 'ditutup_oleh' => 5],
            ['id_periode' => 2, 'id_perusahaan' => 1, 'tahun' => 2026, 'bulan' => 2, 'tgl_mulai' => '2026-02-01', 'tgl_selesai' => '2026-02-28', 'status' => 'ditutup', 'tgl_tutup_buku' => '2026-03-02', 'ditutup_oleh' => 5],
            ['id_periode' => 3, 'id_perusahaan' => 1, 'tahun' => 2026, 'bulan' => 3, 'tgl_mulai' => '2026-03-01', 'tgl_selesai' => '2026-03-31', 'status' => 'ditutup', 'tgl_tutup_buku' => '2026-04-02', 'ditutup_oleh' => 5],
            ['id_periode' => 4, 'id_perusahaan' => 1, 'tahun' => 2026, 'bulan' => 4, 'tgl_mulai' => '2026-04-01', 'tgl_selesai' => '2026-04-30', 'status' => 'aktif', 'tgl_tutup_buku' => null, 'ditutup_oleh' => null],
            ['id_periode' => 5, 'id_perusahaan' => 1, 'tahun' => 2026, 'bulan' => 5, 'tgl_mulai' => '2026-05-01', 'tgl_selesai' => '2026-05-31', 'status' => 'aktif', 'tgl_tutup_buku' => null, 'ditutup_oleh' => null],
        ];
    }

    public static function akunPerkiraan(): array
    {
        return [
            ['kode_akun' => '1', 'id_perusahaan' => 1, 'nama_akun' => 'ASET', 'kategori_akun' => 'aset', 'sub_kategori' => 'header', 'saldo_normal' => 'debit', 'kode_parent' => null, 'is_aktif' => 1],
            ['kode_akun' => '1-1', 'id_perusahaan' => 1, 'nama_akun' => 'Aset Lancar', 'kategori_akun' => 'aset', 'sub_kategori' => 'header', 'saldo_normal' => 'debit', 'kode_parent' => '1', 'is_aktif' => 1],
            ['kode_akun' => '1-1001', 'id_perusahaan' => 1, 'nama_akun' => 'Kas & Bank', 'kategori_akun' => 'aset', 'sub_kategori' => 'lancar', 'saldo_normal' => 'debit', 'kode_parent' => '1-1', 'is_aktif' => 1],
            ['kode_akun' => '1-1101', 'id_perusahaan' => 1, 'nama_akun' => 'Piutang Usaha', 'kategori_akun' => 'aset', 'sub_kategori' => 'lancar', 'saldo_normal' => 'debit', 'kode_parent' => '1-1', 'is_aktif' => 1],
            ['kode_akun' => '1-1102', 'id_perusahaan' => 1, 'nama_akun' => 'PPh 23 Dibayar Dimuka', 'kategori_akun' => 'aset', 'sub_kategori' => 'lancar', 'saldo_normal' => 'debit', 'kode_parent' => '1-1', 'is_aktif' => 1],
            ['kode_akun' => '1-1301', 'id_perusahaan' => 1, 'nama_akun' => 'Persediaan Suku Cadang', 'kategori_akun' => 'aset', 'sub_kategori' => 'lancar', 'saldo_normal' => 'debit', 'kode_parent' => '1-1', 'is_aktif' => 1],
            ['kode_akun' => '1-2', 'id_perusahaan' => 1, 'nama_akun' => 'Aset Tetap', 'kategori_akun' => 'aset', 'sub_kategori' => 'header', 'saldo_normal' => 'debit', 'kode_parent' => '1', 'is_aktif' => 1],
            ['kode_akun' => '1-2001', 'id_perusahaan' => 1, 'nama_akun' => 'Aset Tetap - Genset', 'kategori_akun' => 'aset', 'sub_kategori' => 'tetap', 'saldo_normal' => 'debit', 'kode_parent' => '1-2', 'is_aktif' => 1],
            ['kode_akun' => '1-2002', 'id_perusahaan' => 1, 'nama_akun' => 'Akumulasi Penyusutan', 'kategori_akun' => 'aset', 'sub_kategori' => 'kontra', 'saldo_normal' => 'kredit', 'kode_parent' => '1-2', 'is_aktif' => 1],
            ['kode_akun' => '2', 'id_perusahaan' => 1, 'nama_akun' => 'KEWAJIBAN', 'kategori_akun' => 'kewajiban', 'sub_kategori' => 'header', 'saldo_normal' => 'kredit', 'kode_parent' => null, 'is_aktif' => 1],
            ['kode_akun' => '2-1001', 'id_perusahaan' => 1, 'nama_akun' => 'Utang Usaha', 'kategori_akun' => 'kewajiban', 'sub_kategori' => 'jangka_pendek', 'saldo_normal' => 'kredit', 'kode_parent' => '2', 'is_aktif' => 1],
            ['kode_akun' => '2-2001', 'id_perusahaan' => 1, 'nama_akun' => 'PPN Keluaran', 'kategori_akun' => 'kewajiban', 'sub_kategori' => 'pajak', 'saldo_normal' => 'kredit', 'kode_parent' => '2', 'is_aktif' => 1],
            ['kode_akun' => '3', 'id_perusahaan' => 1, 'nama_akun' => 'EKUITAS', 'kategori_akun' => 'ekuitas', 'sub_kategori' => 'header', 'saldo_normal' => 'kredit', 'kode_parent' => null, 'is_aktif' => 1],
            ['kode_akun' => '3-1001', 'id_perusahaan' => 1, 'nama_akun' => 'Modal Disetor', 'kategori_akun' => 'ekuitas', 'sub_kategori' => 'modal', 'saldo_normal' => 'kredit', 'kode_parent' => '3', 'is_aktif' => 1],
            ['kode_akun' => '4', 'id_perusahaan' => 1, 'nama_akun' => 'PENDAPATAN', 'kategori_akun' => 'pendapatan', 'sub_kategori' => 'header', 'saldo_normal' => 'kredit', 'kode_parent' => null, 'is_aktif' => 1],
            ['kode_akun' => '4-1001', 'id_perusahaan' => 1, 'nama_akun' => 'Pendapatan Sewa Genset', 'kategori_akun' => 'pendapatan', 'sub_kategori' => 'operasional', 'saldo_normal' => 'kredit', 'kode_parent' => '4', 'is_aktif' => 1],
            ['kode_akun' => '4-1002', 'id_perusahaan' => 1, 'nama_akun' => 'Pendapatan Operator & BBM', 'kategori_akun' => 'pendapatan', 'sub_kategori' => 'operasional', 'saldo_normal' => 'kredit', 'kode_parent' => '4', 'is_aktif' => 1],
            ['kode_akun' => '5', 'id_perusahaan' => 1, 'nama_akun' => 'BEBAN', 'kategori_akun' => 'beban', 'sub_kategori' => 'header', 'saldo_normal' => 'debit', 'kode_parent' => null, 'is_aktif' => 1],
            ['kode_akun' => '5-1001', 'id_perusahaan' => 1, 'nama_akun' => 'Beban Penyusutan', 'kategori_akun' => 'beban', 'sub_kategori' => 'non_kas', 'saldo_normal' => 'debit', 'kode_parent' => '5', 'is_aktif' => 1],
            ['kode_akun' => '5-2001', 'id_perusahaan' => 1, 'nama_akun' => 'Beban Servis & Pemeliharaan', 'kategori_akun' => 'beban', 'sub_kategori' => 'operasional', 'saldo_normal' => 'debit', 'kode_parent' => '5', 'is_aktif' => 1],
            ['kode_akun' => '5-3001', 'id_perusahaan' => 1, 'nama_akun' => 'Beban BBM & Operasional', 'kategori_akun' => 'beban', 'sub_kategori' => 'operasional', 'saldo_normal' => 'debit', 'kode_parent' => '5', 'is_aktif' => 1],
            ['kode_akun' => '5-3002', 'id_perusahaan' => 1, 'nama_akun' => 'Beban Transport & Mobilisasi', 'kategori_akun' => 'beban', 'sub_kategori' => 'operasional', 'saldo_normal' => 'debit', 'kode_parent' => '5', 'is_aktif' => 1],
            ['kode_akun' => '7-1001', 'id_perusahaan' => 1, 'nama_akun' => 'Laba/Rugi Pelepasan Aset', 'kategori_akun' => 'pendapatan', 'sub_kategori' => 'non_operasional', 'saldo_normal' => 'kredit', 'kode_parent' => '4', 'is_aktif' => 1],
        ];
    }

    public static function jurnalAkuntansi(): array
    {
        return [
            ['id_jurnal' => 501, 'id_perusahaan' => 1, 'id_periode' => 4, 'no_bukti' => 'JRN-26040-001', 'tanggal' => '2026-04-05', 'jenis_jurnal' => 'sewa', 'referensi_tipe' => 'transaksi_sewa', 'referensi_id' => 1001, 'total_debit' => 83250000, 'total_kredit' => 83250000, 'keterangan' => 'Terbit Invoice INV/2026/04/001 — PT Adhi Konstruksi', 'dibuat_oleh' => 1, 'dibuat_pada' => '2026-04-05 09:14'],
            ['id_jurnal' => 502, 'id_perusahaan' => 1, 'id_periode' => 4, 'no_bukti' => 'JRN-26040-002', 'tanggal' => '2026-04-12', 'jenis_jurnal' => 'pembayaran', 'referensi_tipe' => 'pembayaran', 'referensi_id' => 1, 'total_debit' => 83250000, 'total_kredit' => 83250000, 'keterangan' => 'Pembayaran INV/2026/04/001', 'dibuat_oleh' => 1, 'dibuat_pada' => '2026-04-12 13:02'],
            ['id_jurnal' => 503, 'id_perusahaan' => 1, 'id_periode' => 4, 'no_bukti' => 'JRN-26040-003', 'tanggal' => '2026-04-02', 'jenis_jurnal' => 'pemeliharaan', 'referensi_tipe' => 'pemeliharaan', 'referensi_id' => 2, 'total_debit' => 1675000, 'total_kredit' => 1675000, 'keterangan' => 'Beban servis rutin Genset CMN-250-0232', 'dibuat_oleh' => 2, 'dibuat_pada' => '2026-04-02 16:30'],
            ['id_jurnal' => 504, 'id_perusahaan' => 1, 'id_periode' => 4, 'no_bukti' => 'JRN-26040-004', 'tanggal' => '2026-04-30', 'jenis_jurnal' => 'penyusutan', 'referensi_tipe' => 'scheduler', 'referensi_id' => null, 'total_debit' => 32450000, 'total_kredit' => 32450000, 'keterangan' => 'Depresiasi bulanan April 2026 (8 unit)', 'dibuat_oleh' => 0, 'dibuat_pada' => '2026-04-30 23:59'],
            ['id_jurnal' => 505, 'id_perusahaan' => 1, 'id_periode' => 4, 'no_bukti' => 'JRN-26040-005', 'tanggal' => '2026-04-08', 'jenis_jurnal' => 'sewa', 'referensi_tipe' => 'transaksi_sewa', 'referensi_id' => 1002, 'total_debit' => 209235000, 'total_kredit' => 209235000, 'keterangan' => 'Terbit Invoice INV/2026/04/002 — PT Borneo Mining', 'dibuat_oleh' => 1, 'dibuat_pada' => '2026-04-08 10:00'],
            ['id_jurnal' => 506, 'id_perusahaan' => 1, 'id_periode' => 4, 'no_bukti' => 'JRN-26040-006', 'tanggal' => '2026-04-22', 'jenis_jurnal' => 'beban_operasional', 'referensi_tipe' => 'transaksi_sewa', 'referensi_id' => 1002, 'total_debit' => 4200000, 'total_kredit' => 4200000, 'keterangan' => 'Tambahan BBM site Tenggarong', 'dibuat_oleh' => 1, 'dibuat_pada' => '2026-04-22 14:20'],
        ];
    }

    public static function detailJurnal(): array
    {
        return [
            ['id_detail_jurnal' => 1, 'id_jurnal' => 501, 'kode_akun' => '1-1101', 'debit' => 83250000, 'kredit' => 0, 'keterangan' => 'Piutang INV/2026/04/001', 'urutan' => 1],
            ['id_detail_jurnal' => 2, 'id_jurnal' => 501, 'kode_akun' => '4-1001', 'debit' => 0, 'kredit' => 62500000, 'keterangan' => 'Pendapatan sewa unit', 'urutan' => 2],
            ['id_detail_jurnal' => 3, 'id_jurnal' => 501, 'kode_akun' => '4-1002', 'debit' => 0, 'kredit' => 12500000, 'keterangan' => 'Operator + mobdemob + BBM', 'urutan' => 3],
            ['id_detail_jurnal' => 4, 'id_jurnal' => 501, 'kode_akun' => '2-2001', 'debit' => 0, 'kredit' => 8250000, 'keterangan' => 'PPN 11% keluaran', 'urutan' => 4],
            ['id_detail_jurnal' => 5, 'id_jurnal' => 502, 'kode_akun' => '1-1001', 'debit' => 83250000, 'kredit' => 0, 'keterangan' => 'Penerimaan transfer BCA', 'urutan' => 1],
            ['id_detail_jurnal' => 6, 'id_jurnal' => 502, 'kode_akun' => '1-1101', 'debit' => 0, 'kredit' => 83250000, 'keterangan' => 'Pelunasan piutang', 'urutan' => 2],
            ['id_detail_jurnal' => 7, 'id_jurnal' => 503, 'kode_akun' => '5-2001', 'debit' => 1675000, 'kredit' => 0, 'keterangan' => 'Pemakaian suku cadang', 'urutan' => 1],
            ['id_detail_jurnal' => 8, 'id_jurnal' => 503, 'kode_akun' => '1-1301', 'debit' => 0, 'kredit' => 1675000, 'keterangan' => 'Pengurangan persediaan', 'urutan' => 2],
            ['id_detail_jurnal' => 9, 'id_jurnal' => 504, 'kode_akun' => '5-1001', 'debit' => 32450000, 'kredit' => 0, 'keterangan' => 'Beban penyusutan April', 'urutan' => 1],
            ['id_detail_jurnal' => 10, 'id_jurnal' => 504, 'kode_akun' => '1-2002', 'debit' => 0, 'kredit' => 32450000, 'keterangan' => 'Akumulasi penyusutan', 'urutan' => 2],
            ['id_detail_jurnal' => 11, 'id_jurnal' => 505, 'kode_akun' => '1-1101', 'debit' => 209235000, 'kredit' => 0, 'keterangan' => 'Piutang INV/2026/04/002', 'urutan' => 1],
            ['id_detail_jurnal' => 12, 'id_jurnal' => 505, 'kode_akun' => '4-1001', 'debit' => 0, 'kredit' => 144000000, 'keterangan' => 'Pendapatan sewa unit', 'urutan' => 2],
            ['id_detail_jurnal' => 13, 'id_jurnal' => 505, 'kode_akun' => '4-1002', 'debit' => 0, 'kredit' => 44500000, 'keterangan' => 'Operator + mobdemob + BBM', 'urutan' => 3],
            ['id_detail_jurnal' => 14, 'id_jurnal' => 505, 'kode_akun' => '2-2001', 'debit' => 0, 'kredit' => 20735000, 'keterangan' => 'PPN 11% keluaran', 'urutan' => 4],
            ['id_detail_jurnal' => 15, 'id_jurnal' => 506, 'kode_akun' => '5-3001', 'debit' => 4200000, 'kredit' => 0, 'keterangan' => 'BBM tambahan', 'urutan' => 1],
            ['id_detail_jurnal' => 16, 'id_jurnal' => 506, 'kode_akun' => '1-1001', 'debit' => 0, 'kredit' => 4200000, 'keterangan' => 'Pengeluaran kas', 'urutan' => 2],
        ];
    }

    /* =========================================================
     | Derived datasets
     |=========================================================*/

    /** Materialized availability calendar — derived from detail_sewa & pemeliharaan. */
    public static function jadwalKetersediaan(): array
    {
        $arr = [];
        $id = 1;
        foreach (self::genset() as $g) {
            for ($d = 1; $d <= 30; $d++) {
                $date = strtotime(sprintf('2026-04-%02d', $d));
                $ds = sprintf('2026-04-%02d', $d);
                $status = 'tersedia';
                foreach (self::detailSewa() as $x) {
                    if ($x['id_genset'] === $g['id_genset']
                        && strtotime($x['start_date']) <= $date
                        && strtotime($x['end_date']) >= $date) {
                        $status = 'disewa';
                        break;
                    }
                }
                if ($status === 'tersedia') {
                    foreach (self::pemeliharaan() as $p) {
                        if ($p['id_genset'] === $g['id_genset']
                            && strtotime($p['tgl_mulai_servis']) <= $date
                            && (! $p['tgl_selesai'] || strtotime($p['tgl_selesai']) >= $date)) {
                            $status = 'maintenance';
                            break;
                        }
                    }
                }
                if ($status === 'tersedia' && in_array($g['status'], ['rusak', 'terjual'], true)) {
                    $status = 'tidak_tersedia';
                }
                $arr[] = ['id_jadwal' => $id++, 'id_genset' => $g['id_genset'], 'tanggal' => $ds, 'status' => $status];
            }
        }

        return $arr;
    }

    /** Depreciation schedule — one row per genset for the current month (with snapshots). */
    public static function jadwalPenyusutan(): array
    {
        $rows = [];
        $i = 0;
        foreach (self::genset() as $g) {
            $info = self::depresiasiInfo($g);
            $rows[] = [
                'id_penyusutan' => 900 + $i,
                'id_genset' => $g['id_genset'],
                'id_perusahaan' => 1,
                'id_periode' => 4,
                'id_jurnal' => 504,
                'periode_bulan' => '2026-04',
                'harga_perolehan' => $g['harga_perolehan'],
                'nilai_residu' => $g['nilai_residu_aktual'],
                'umur_ekonomis_bulan' => $g['umur_ekonomis_aktual'],
                'beban_penyusutan' => $info['monthly'],
                'akumulasi_penyusutan' => $info['accumulated'],
                'nilai_buku' => $info['bookValue'],
                'status_jurnal' => 'posted',
            ];
            $i++;
        }

        return $rows;
    }

    /* =========================================================
     | Computations & helpers
     |=========================================================*/

    /** Straight-line depreciation figures for a genset, evaluated through Apr 2026. */
    public static function depresiasiInfo(array $g): array
    {
        $monthly = (int) round(($g['harga_perolehan'] - $g['nilai_residu_aktual']) / $g['umur_ekonomis_aktual']);
        $acqYear = (int) date('Y', strtotime($g['tgl_perolehan']));
        $acqMonth = (int) date('n', strtotime($g['tgl_perolehan']));
        // through April 2026 (month index 4)
        $monthsElapsed = max(0, (2026 - $acqYear) * 12 + (4 - $acqMonth));
        $depreciable = $g['harga_perolehan'] - $g['nilai_residu_aktual'];
        $accumulated = min($monthly * $monthsElapsed, $depreciable);
        $bookValue = $g['harga_perolehan'] - $accumulated;

        return [
            'monthly' => $monthly,
            'accumulated' => $accumulated,
            'bookValue' => $bookValue,
            'monthsElapsed' => $monthsElapsed,
        ];
    }

    /** Total / paid / outstanding for a transaksi_sewa row. */
    public static function sewaOutstanding(array $sewa): array
    {
        // Total kas yang akan diterima = tagihan + PPN - PPh (PPh dipotong pelanggan).
        $total = ($sewa['total_tagihan'] ?? 0) + ($sewa['pajak'] ?? 0) - ($sewa['pph'] ?? 0);
        $paid = 0;
        foreach (self::pembayaran() as $p) {
            if ($p['id_sewa'] === $sewa['id_sewa']) {
                $paid += $p['nominal_bayar'];
            }
        }

        return ['total' => $total, 'paid' => $paid, 'sisa' => $total - $paid];
    }

    /** Human-readable status labels (lowercase enum → Title Case for UI). */
    public static function statusLabels(): array
    {
        return [
            'di_perusahaan' => 'Di Perusahaan', 'di_proyek' => 'Di Proyek', 'di_gudang' => 'Di Gudang',
            'terjual' => 'Terjual', 'rusak' => 'Rusak',
            'pesan' => 'Pesanan', 'deal' => 'Aktif', 'dibatalkan' => 'Dibatalkan', 'selesai' => 'Selesai',
            'belum_bayar' => 'Belum Bayar', 'dp' => 'DP', 'lunas' => 'Lunas', 'overdue' => 'Overdue',
            'tersedia' => 'Tersedia', 'disewa' => 'Disewa', 'maintenance' => 'Maintenance', 'tidak_tersedia' => 'Tidak Tersedia',
            'aktif' => 'Aktif', 'ditutup' => 'Ditutup',
            'pending' => 'Pending', 'posted' => 'Posted',
            'rutin' => 'Rutin', 'perbaikan' => 'Perbaikan', 'overhaul' => 'Overhaul',
            'pengambilan' => 'Pengambilan', 'pengembalian' => 'Pengembalian',
            'owner' => 'Owner', 'admin' => 'Admin', 'operator' => 'Operator', 'teknisi' => 'Teknisi', 'akuntan' => 'Akuntan',
        ];
    }

    /** Index an array of rows by a key column. */
    public static function indexBy(array $rows, string $key): array
    {
        $out = [];
        foreach ($rows as $r) {
            $out[$r[$key]] = $r;
        }

        return $out;
    }

    /**
     * Full dataset untuk satu tenant — dibaca LANGSUNG dari database
     * (kedua schema), lalu dibentuk ulang ke struktur array yang sama
     * seperti versi mock, sehingga seluruh view Blade tidak perlu diubah.
     *
     * Tenant ditentukan dari pengguna yang sedang login (multi-tenant scope).
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $tid = \Illuminate\Support\Facades\Auth::check()
            ? (int) \Illuminate\Support\Facades\Auth::user()->id_perusahaan
            : 1;

        $perusahaan = \App\Models\Perusahaan::where('id_perusahaan', $tid)->get()->toArray();
        $pengguna = \App\Models\Pengguna::where('id_perusahaan', $tid)->get()->toArray();
        $merek = \App\Models\Merek::where('id_perusahaan', $tid)->get()->toArray();
        $pelanggan = \App\Models\Pelanggan::where('id_perusahaan', $tid)->get()->toArray();
        $supplier = \App\Models\Supplier::where('id_perusahaan', $tid)->get()->toArray();
        $kategori = \App\Models\KategoriGenset::where('id_perusahaan', $tid)->get()->toArray();
        $genset = \App\Models\Genset::where('id_perusahaan', $tid)->get()->toArray();
        $gensetIds = array_column($genset, 'id_genset') ?: [0];
        $transaksiSewa = \App\Models\TransaksiSewa::where('id_perusahaan', $tid)->get()->toArray();
        $sewaIds = array_column($transaksiSewa, 'id_sewa') ?: [0];
        $detailSewa = \App\Models\DetailSewa::whereIn('id_sewa', $sewaIds)->get()->toArray();
        $pengembalian = \App\Models\Pengembalian::whereIn('id_sewa', $sewaIds)->get()->toArray();
        $pembayaran = \App\Models\Pembayaran::where('id_perusahaan', $tid)->get()->toArray();
        $sukuCadang = \App\Models\SukuCadang::where('id_perusahaan', $tid)->get()->toArray();
        $pemeliharaan = \App\Models\Pemeliharaan::where('id_perusahaan', $tid)->get()->toArray();
        $pemIds = array_column($pemeliharaan, 'id_pemeliharaan') ?: [0];
        $detailPemeliharaan = \App\Models\DetailPemeliharaan::whereIn('id_pemeliharaan', $pemIds)->get()->toArray();
        $penjualan = \App\Models\PenjualanGenset::where('id_perusahaan', $tid)->get()->toArray();
        $jadwalKetersediaan = \App\Models\JadwalKetersediaan::whereIn('id_genset', $gensetIds)->get()->toArray();

        $akun = \App\Models\AkunPerkiraan::where('id_perusahaan', $tid)->get()->toArray();
        $periode = \App\Models\PeriodeAkuntansi::where('id_perusahaan', $tid)->get()->toArray();
        $jurnal = \App\Models\JurnalAkuntansi::where('id_perusahaan', $tid)->get()->toArray();
        $jurnalIds = array_column($jurnal, 'id_jurnal') ?: [0];
        $detailJurnal = \App\Models\DetailJurnal::whereIn('id_jurnal', $jurnalIds)->get()->toArray();
        $jadwalPenyusutan = array_map(function ($r) {
            $r['periode_bulan'] = substr((string) $r['periode_bulan'], 0, 7); // DATE → 'Y-m'
            return $r;
        }, \App\Models\JadwalPenyusutan::where('id_perusahaan', $tid)->get()->toArray());

        $detailSewaBySewa = [];
        foreach ($transaksiSewa as $s) {
            $detailSewaBySewa[$s['id_sewa']] = array_values(array_filter(
                $detailSewa,
                fn ($d) => $d['id_sewa'] == $s['id_sewa']
            ));
        }

        $tenantRow = collect($perusahaan)->firstWhere('id_perusahaan', $tid) ?? ($perusahaan[0] ?? null);

        return self::$cache = [
            'TENANT' => $tenantRow,
            'perusahaan' => $perusahaan,
            'pengguna' => $pengguna,
            'merek' => $merek,
            'pelanggan' => $pelanggan,
            'supplier' => $supplier,
            'kategori_genset' => $kategori,
            'genset' => $genset,
            'transaksi_sewa' => $transaksiSewa,
            'detail_sewa' => $detailSewa,
            'pengembalian' => $pengembalian,
            'pembayaran' => $pembayaran,
            'suku_cadang' => $sukuCadang,
            'pemeliharaan' => $pemeliharaan,
            'detail_pemeliharaan' => $detailPemeliharaan,
            'penjualan_genset' => $penjualan,
            'periode_akuntansi' => $periode,
            'akun_perkiraan' => $akun,
            'jurnal_akuntansi' => $jurnal,
            'detail_jurnal' => $detailJurnal,
            'jadwal_ketersediaan' => $jadwalKetersediaan,
            'jadwal_penyusutan' => $jadwalPenyusutan,
            // lookups — entri kunci '' adalah fallback untuk FK nullable
            // (genset boleh tanpa merek/kategori/supplier; view tidak boleh crash)
            'kategoriById' => self::indexBy($kategori, 'id_kategori')
                + ['' => ['id_kategori' => null, 'kapasitas' => '—', 'umur_ekonomis_default' => 0, 'estimasi_nilai_residu' => 0]],
            'supplierById' => self::indexBy($supplier, 'id_supplier')
                + ['' => ['id_supplier' => null, 'nama_supplier' => '—', 'pic_kontak' => '', 'no_telepon' => '', 'email' => '', 'alamat' => '']],
            'merekById' => self::indexBy($merek, 'id_merek')
                + ['' => ['id_merek' => null, 'nama_merek' => '—', 'negara_asal' => '', 'keterangan' => '']],
            'gensetById' => self::indexBy($genset, 'id_genset'),
            'pelangganById' => self::indexBy($pelanggan, 'id_pelanggan'),
            'penggunaById' => self::indexBy($pengguna, 'id_pengguna'),
            'partById' => self::indexBy($sukuCadang, 'id_part'),
            'sewaById' => self::indexBy($transaksiSewa, 'id_sewa'),
            'jurnalById' => self::indexBy($jurnal, 'id_jurnal'),
            'periodeById' => self::indexBy($periode, 'id_periode'),
            'akunByKode' => self::indexBy($akun, 'kode_akun'),
            'detailSewaBySewa' => $detailSewaBySewa,
        ];
    }
}
