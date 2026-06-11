<?php

namespace Tests\Feature;

use App\Models\Genset;
use App\Models\JadwalKetersediaan;
use App\Models\JadwalPenyusutan;
use App\Models\JurnalAkuntansi;
use App\Models\KategoriGenset;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\PeriodeAkuntansi;
use App\Models\Perusahaan;
use App\Models\TransaksiSewa;
use App\Services\DepreciationService;
use App\Services\PeriodClosingService;
use App\Support\VoltraData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Uji coba alur utama Voltra (bahan sub-bab Verifikasi BAB 6 TA).
 * Setiap test membuat tenant segar di dalam transaksi yang otomatis
 * di-rollback, sehingga data dev tidak terpengaruh.
 */
class VoltraFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['voltra', 'voltra_akuntansi'];

    /** Tenant lengkap: perusahaan + COA + owner + teknisi + pelanggan + 1 genset. */
    private function buatTenant(): array
    {
        $perusahaan = Perusahaan::create([
            'nama_perusahaan' => 'PT Uji Voltra',
            'status_aktif' => true,
            'tgl_bergabung' => now()->toDateString(),
        ]);
        $tid = $perusahaan->id_perusahaan;

        $coa = array_map(fn ($a) => array_merge($a, ['id_perusahaan' => $tid]), VoltraData::akunPerkiraan());
        DB::connection('voltra_akuntansi')->table('akun_perkiraan')->insert($coa);

        $owner = Pengguna::create([
            'id_perusahaan' => $tid, 'nama' => 'Owner Uji', 'email' => 'owner@uji.test',
            'password' => Hash::make('rahasia123'), 'role' => 'owner', 'avatar' => 'OU',
        ]);
        $teknisi = Pengguna::create([
            'id_perusahaan' => $tid, 'nama' => 'Teknisi Uji', 'email' => 'teknisi@uji.test',
            'password' => Hash::make('rahasia123'), 'role' => 'teknisi', 'avatar' => 'TU',
        ]);
        $pelanggan = Pelanggan::create([
            'id_perusahaan' => $tid, 'nama_perusahaan' => 'CV Pelanggan Uji',
            'pic_kontak' => 'Budi', 'alamat_lengkap' => 'Jl. Uji 1', 'no_telepon' => '081234',
        ]);
        $kategori = KategoriGenset::create([
            'id_perusahaan' => $tid, 'kapasitas' => '100 kVA',
            'umur_ekonomis_default' => 96, 'estimasi_nilai_residu' => 0,
        ]);
        $genset = Genset::create([
            'id_perusahaan' => $tid, 'id_kategori' => $kategori->id_kategori,
            'nomor_seri' => 'GEN-UJI-001', 'tgl_perolehan' => now()->subYear()->toDateString(),
            'harga_perolehan' => 96000000, 'nilai_residu_aktual' => 0,
            'umur_ekonomis_aktual' => 96, 'status' => 'di_gudang', 'lokasi_terkini' => 'Gudang utama',
        ]);

        return compact('perusahaan', 'tid', 'owner', 'teknisi', 'pelanggan', 'genset');
    }

    private function payloadSewa(array $t, string $mulai, string $selesai): array
    {
        return [
            'id_pelanggan' => $t['pelanggan']->id_pelanggan,
            'items' => [[
                'id_genset' => $t['genset']->id_genset,
                'start_date' => $mulai,
                'end_date' => $selesai,
                'harga_sewa_unit' => 1000000,
            ]],
        ];
    }

    // ---------- Autentikasi & RBAC ----------

    public function test_teknisi_ditolak_dari_halaman_keuangan(): void
    {
        $t = $this->buatTenant();

        $this->actingAs($t['teknisi'])->get('/opex')->assertForbidden();
        $this->actingAs($t['teknisi'])->get('/accounting')->assertForbidden();
        $this->actingAs($t['teknisi'])->get('/period')->assertForbidden();
        $this->actingAs($t['teknisi'])->get('/users')->assertForbidden();
    }

    public function test_owner_dapat_membuka_seluruh_halaman(): void
    {
        $t = $this->buatTenant();

        foreach (['dashboard', 'rental', 'assets', 'accounting', 'period', 'reports'] as $page) {
            $this->actingAs($t['owner'])->get('/' . $page)->assertOk();
        }
    }

    // ---------- Transaksi sewa & penjurnalan otomatis ----------

    public function test_membuat_sewa_menghasilkan_jurnal_seimbang_dan_kalender_terkunci(): void
    {
        $t = $this->buatTenant();

        $res = $this->actingAs($t['owner'])
            ->postJson('/aksi/rental', $this->payloadSewa($t, now()->toDateString(), now()->addDays(4)->toDateString()));

        $res->assertCreated();
        $idJurnal = $res->json('jurnal.id_jurnal');
        $jurnal = JurnalAkuntansi::findOrFail($idJurnal);

        $this->assertSame((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 'Jurnal sewa harus seimbang');
        $this->assertGreaterThan(0, (float) $jurnal->total_debit);
        $this->assertSame(5, JadwalKetersediaan::where('id_genset', $t['genset']->id_genset)
            ->where('status', 'disewa')->count(), 'Kalender 5 hari harus terkunci');
    }

    public function test_sewa_ditolak_bila_jadwal_bentrok(): void
    {
        $t = $this->buatTenant();
        $mulai = now()->toDateString();
        $selesai = now()->addDays(4)->toDateString();

        $this->actingAs($t['owner'])->postJson('/aksi/rental', $this->payloadSewa($t, $mulai, $selesai))->assertCreated();
        $this->actingAs($t['owner'])->postJson('/aksi/rental', $this->payloadSewa($t, $mulai, $selesai))
            ->assertStatus(422);

        $this->assertSame(1, TransaksiSewa::where('id_perusahaan', $t['tid'])->count(), 'Kontrak kedua tidak boleh tersimpan');
    }

    public function test_sewa_ditolak_bila_periode_ditutup_tanpa_data_yatim(): void
    {
        $t = $this->buatTenant();
        PeriodeAkuntansi::create([
            'id_perusahaan' => $t['tid'], 'tahun' => (int) now()->format('Y'), 'bulan' => (int) now()->format('n'),
            'tgl_mulai' => now()->startOfMonth()->toDateString(), 'tgl_selesai' => now()->endOfMonth()->toDateString(),
            'status' => 'ditutup',
        ]);

        $this->actingAs($t['owner'])
            ->postJson('/aksi/rental', $this->payloadSewa($t, now()->toDateString(), now()->addDays(4)->toDateString()))
            ->assertStatus(422);

        $this->assertSame(0, TransaksiSewa::where('id_perusahaan', $t['tid'])->count(), 'Tidak boleh ada invoice yatim');
        $this->assertSame(0, JadwalKetersediaan::where('id_genset', $t['genset']->id_genset)->count(), 'Kalender tidak boleh terkunci');
    }

    public function test_genset_milik_tenant_lain_tidak_bisa_disewakan(): void
    {
        $t = $this->buatTenant();
        $lain = $this->buatTenantKedua();

        $payload = $this->payloadSewa($t, now()->toDateString(), now()->addDay()->toDateString());
        $payload['items'][0]['id_genset'] = $lain['genset']->id_genset;

        $this->actingAs($t['owner'])->postJson('/aksi/rental', $payload)->assertStatus(422);
    }

    private function buatTenantKedua(): array
    {
        $perusahaan = Perusahaan::create([
            'nama_perusahaan' => 'PT Tenant Lain', 'status_aktif' => true,
            'tgl_bergabung' => now()->toDateString(),
        ]);
        $kategori = KategoriGenset::create([
            'id_perusahaan' => $perusahaan->id_perusahaan, 'kapasitas' => '60 kVA',
            'umur_ekonomis_default' => 96, 'estimasi_nilai_residu' => 0,
        ]);
        $genset = Genset::create([
            'id_perusahaan' => $perusahaan->id_perusahaan, 'id_kategori' => $kategori->id_kategori,
            'nomor_seri' => 'GEN-LAIN-001', 'tgl_perolehan' => now()->toDateString(),
            'harga_perolehan' => 50000000, 'nilai_residu_aktual' => 0,
            'umur_ekonomis_aktual' => 96, 'status' => 'di_gudang', 'lokasi_terkini' => 'Gudang utama',
        ]);

        return compact('perusahaan', 'genset');
    }

    // ---------- Pembayaran ----------

    public function test_pembayaran_melebihi_sisa_tagihan_ditolak(): void
    {
        $t = $this->buatTenant();
        $res = $this->actingAs($t['owner'])
            ->postJson('/aksi/rental', $this->payloadSewa($t, now()->toDateString(), now()->addDays(4)->toDateString()));
        $res->assertCreated();

        $idSewa = $res->json('sewa.id_sewa');
        $total = (float) $res->json('sewa.total_tagihan') + (float) $res->json('sewa.pajak');

        // bayar melebihi total → ditolak
        $this->actingAs($t['owner'])->postJson('/aksi/payment', [
            'id_sewa' => $idSewa, 'nominal_bayar' => $total + 1000000, 'metode_bayar' => 'transfer',
        ])->assertStatus(422);

        // bayar pas → lunas + jurnal seimbang
        $bayar = $this->actingAs($t['owner'])->postJson('/aksi/payment', [
            'id_sewa' => $idSewa, 'nominal_bayar' => $total, 'metode_bayar' => 'transfer',
        ]);
        $bayar->assertCreated();
        $this->assertSame('lunas', $bayar->json('sewa.status_pembayaran'));
        $this->assertEquals($bayar->json('jurnal.total_debit'), $bayar->json('jurnal.total_kredit'));
    }

    // ---------- Jurnal manual & beban operasional ----------

    public function test_jurnal_manual_tidak_seimbang_ditolak(): void
    {
        $t = $this->buatTenant();

        $this->actingAs($t['owner'])->postJson('/aksi/journal/manual', [
            'tanggal' => now()->toDateString(),
            'keterangan' => 'uji tidak seimbang',
            'lines' => [
                ['kode_akun' => '1-1001', 'debit' => 500000],
                ['kode_akun' => '3-1001', 'kredit' => 300000],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, JurnalAkuntansi::where('id_perusahaan', $t['tid'])->count());
    }

    public function test_beban_operasional_dengan_kode_akun_tidak_dikenal_ditolak(): void
    {
        $t = $this->buatTenant();

        $this->actingAs($t['owner'])->postJson('/aksi/opex', [
            'tanggal' => now()->toDateString(), 'nominal' => 100000,
            'kode_akun' => '9-XXXX', 'keterangan' => 'akun ngawur',
        ])->assertStatus(422);

        $this->assertSame(0, JurnalAkuntansi::where('id_perusahaan', $t['tid'])->count(), 'Tidak boleh ada jurnal yatim');
    }

    // ---------- Depresiasi Straight Line ----------

    public function test_depresiasi_menghitung_benar_dan_idempotent(): void
    {
        $t = $this->buatTenant();
        $service = app(DepreciationService::class);

        $r1 = $service->runForPeriod($t['tid'], (int) now()->format('Y'), (int) now()->format('n'));

        // D = (C - R) / N = (96.000.000 - 0) / 96 = 1.000.000 per bulan
        $this->assertFalse($r1['skipped']);
        $this->assertSame(1, $r1['unit']);
        $this->assertSame(1000000.0, $r1['total_beban']);
        $this->assertNotNull($r1['id_jurnal']);

        $jurnal = JurnalAkuntansi::findOrFail($r1['id_jurnal']);
        $this->assertEquals((float) $jurnal->total_debit, (float) $jurnal->total_kredit);
        $this->assertSame('posted', JadwalPenyusutan::where('id_perusahaan', $t['tid'])->first()->status_jurnal);

        // run kedua periode sama → dilewati (idempotent)
        $r2 = $service->runForPeriod($t['tid'], (int) now()->format('Y'), (int) now()->format('n'));
        $this->assertTrue($r2['skipped']);
        $this->assertSame(1, JadwalPenyusutan::where('id_perusahaan', $t['tid'])->count());
    }

    // ---------- Tutup buku ----------

    public function test_tutup_buku_memvalidasi_lalu_mengunci_penjurnalan(): void
    {
        $t = $this->buatTenant();
        app(DepreciationService::class)->runForPeriod($t['tid'], (int) now()->format('Y'), (int) now()->format('n'));

        $periode = PeriodeAkuntansi::where('id_perusahaan', $t['tid'])->firstOrFail();
        $closing = app(PeriodClosingService::class);

        $this->assertTrue($closing->validate($periode)['valid']);
        $closing->close($periode, $t['owner']->id_pengguna);
        $this->assertSame('ditutup', $periode->refresh()->status);

        // setelah ditutup, transaksi baru pada periode itu ditolak
        $this->actingAs($t['owner'])->postJson('/aksi/opex', [
            'tanggal' => now()->toDateString(), 'nominal' => 50000,
            'kode_akun' => '5-3001', 'keterangan' => 'uji setelah tutup buku',
        ])->assertStatus(422);
    }

    public function test_periode_ditutup_bisa_dibuka_kembali_dan_jurnal_aktif_lagi(): void
    {
        $t = $this->buatTenant();
        app(DepreciationService::class)->runForPeriod($t['tid'], (int) now()->format('Y'), (int) now()->format('n'));

        $periode = PeriodeAkuntansi::where('id_perusahaan', $t['tid'])->firstOrFail();
        app(PeriodClosingService::class)->close($periode, $t['owner']->id_pengguna);

        // teknisi tidak boleh membuka periode (RBAC)
        $this->actingAs($t['teknisi'])
            ->postJson('/aksi/period/' . $periode->id_periode . '/reopen')
            ->assertForbidden();

        // owner membuka kembali
        $this->actingAs($t['owner'])
            ->postJson('/aksi/period/' . $periode->id_periode . '/reopen')
            ->assertOk();
        $this->assertSame('aktif', $periode->refresh()->status);
        $this->assertNull($periode->ditutup_oleh);

        // penjurnalan aktif lagi
        $this->actingAs($t['owner'])->postJson('/aksi/opex', [
            'tanggal' => now()->toDateString(), 'nominal' => 50000,
            'kode_akun' => '5-3001', 'keterangan' => 'uji setelah dibuka kembali',
        ])->assertCreated();

        // membuka periode yang masih aktif → ditolak ramah
        $this->actingAs($t['owner'])
            ->postJson('/aksi/period/' . $periode->id_periode . '/reopen')
            ->assertStatus(422);
    }

    // ---------- Registrasi & kode undangan ----------

    public function test_registrasi_join_butuh_kode_undangan_yang_benar(): void
    {
        $t = $this->buatTenant();
        $t['perusahaan']->update(['kode_undangan' => 'UJIKODE1']);

        // kode salah → ditolak, tidak ada akun dibuat
        $this->post('/register', [
            'nama' => 'Karyawan Baru', 'email' => 'karyawan@uji.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'mode' => 'join', 'kode_undangan' => 'SALAH123',
        ])->assertSessionHasErrors('kode_undangan');
        $this->assertSame(0, Pengguna::where('email', 'karyawan@uji.test')->count());

        // kode benar → akun operator dibuat di perusahaan yang tepat
        $this->post('/register', [
            'nama' => 'Karyawan Baru', 'email' => 'karyawan@uji.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'mode' => 'join', 'kode_undangan' => 'ujikode1', // case-insensitive
        ])->assertRedirect(route('login'));

        $baru = Pengguna::where('email', 'karyawan@uji.test')->firstOrFail();
        $this->assertSame($t['tid'], (int) $baru->id_perusahaan);
        $this->assertSame('operator', $baru->role);
    }

    public function test_halaman_registrasi_tidak_membocorkan_daftar_perusahaan(): void
    {
        $t = $this->buatTenant();

        $res = $this->get('/register');
        $res->assertOk();
        $res->assertDontSee($t['perusahaan']->nama_perusahaan);
    }

    public function test_registrasi_perusahaan_baru_mendapat_kode_undangan_dan_coa(): void
    {
        $this->post('/register', [
            'nama' => 'Owner Baru', 'email' => 'ownerbaru@uji.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'mode' => 'new', 'nama_perusahaan' => 'PT Registrasi Uji',
        ])->assertRedirect(route('login'));

        $p = Perusahaan::where('nama_perusahaan', 'PT Registrasi Uji')->firstOrFail();
        $this->assertNotNull($p->kode_undangan);
        $this->assertSame(8, strlen($p->kode_undangan));

        $jumlahAkun = DB::connection('voltra_akuntansi')->table('akun_perkiraan')
            ->where('id_perusahaan', $p->id_perusahaan)->count();
        $this->assertGreaterThan(10, $jumlahAkun, 'COA standar harus ter-seed, termasuk akun PPh 1-1102');
        $this->assertSame(1, DB::connection('voltra_akuntansi')->table('akun_perkiraan')
            ->where('id_perusahaan', $p->id_perusahaan)->where('kode_akun', '1-1102')->count());
    }
}
