<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models;
use Illuminate\Http\Request;

/**
 * Endpoint baca generik (tenant-scoped) untuk master & transaksi Voltra.
 * GET /api/{resource}        → index
 * GET /api/{resource}/{id}   → show
 */
class ResourceApiController extends Controller
{
    /** slug => [model, kolom PK, scoped-per-tenant?] */
    protected array $map = [
        'genset' => [Models\Genset::class, 'id_genset', true],
        'pelanggan' => [Models\Pelanggan::class, 'id_pelanggan', true],
        'supplier' => [Models\Supplier::class, 'id_supplier', true],
        'merek' => [Models\Merek::class, 'id_merek', true],
        'kategori-genset' => [Models\KategoriGenset::class, 'id_kategori', true],
        'pengguna' => [Models\Pengguna::class, 'id_pengguna', true],
        'suku-cadang' => [Models\SukuCadang::class, 'id_part', true],
        'transaksi-sewa' => [Models\TransaksiSewa::class, 'id_sewa', true],
        'pembayaran' => [Models\Pembayaran::class, 'id_pembayaran', true],
        'pemeliharaan' => [Models\Pemeliharaan::class, 'id_pemeliharaan', true],
        'pengembalian' => [Models\Pengembalian::class, 'id_pengembalian', false],
        'penjualan-genset' => [Models\PenjualanGenset::class, 'id_penjualan', true],
        'jadwal-ketersediaan' => [Models\JadwalKetersediaan::class, 'id_jadwal', false],
        'akun-perkiraan' => [Models\AkunPerkiraan::class, 'kode_akun', true],
        'periode' => [Models\PeriodeAkuntansi::class, 'id_periode', true],
        'jurnal' => [Models\JurnalAkuntansi::class, 'id_jurnal', true],
        'jadwal-penyusutan' => [Models\JadwalPenyusutan::class, 'id_penyusutan', true],
    ];

    public function index(Request $request, string $resource)
    {
        [$model, , $scoped] = $this->resolve($resource);
        $query = $model::query();
        if ($scoped) {
            $query->where('id_perusahaan', $request->user()->id_perusahaan);
        }
        if ($request->filled('q') && $resource === 'genset') {
            $query->where('nomor_seri', 'like', '%' . $request->q . '%');
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 50)));
    }

    public function show(Request $request, string $resource, string $id)
    {
        [$model, $pk, $scoped] = $this->resolve($resource);
        $query = $model::where($pk, $id);
        if ($scoped) {
            $query->where('id_perusahaan', $request->user()->id_perusahaan);
        }
        $row = $query->first();

        return $row
            ? response()->json($row)
            : response()->json(['message' => 'Data tidak ditemukan.'], 404);
    }

    protected function resolve(string $resource): array
    {
        abort_unless(isset($this->map[$resource]), 404, "Resource '$resource' tidak dikenal.");

        return $this->map[$resource];
    }
}
