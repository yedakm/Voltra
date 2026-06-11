<?php

namespace App\Http\Controllers;

use App\Models\AkunPerkiraan;
use App\Models\DetailPemeliharaan;
use App\Models\KategoriGenset;
use App\Models\Merek;
use App\Models\Pelanggan;
use App\Models\Pemeliharaan;
use App\Models\Pengguna;
use App\Models\SukuCadang;
use App\Models\Supplier;
use App\Models\TransaksiSewa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * CRUD master data — dipanggil dari drawer "+ Baru" pada halaman Master Data.
 */
class MasterController extends Controller
{
    /** POST /aksi/master/{type} — simpan satu baris master data. */
    public function store(Request $request, string $type)
    {
        $tid = $request->user()->id_perusahaan;

        return match ($type) {
            'pelanggan' => $this->json(Pelanggan::create(
                $request->validate([
                    'nama_perusahaan' => ['required', 'string', 'max:150'],
                    'pic_kontak' => ['nullable', 'string'],
                    'alamat_lengkap' => ['nullable', 'string'],
                    'npwp' => ['nullable', 'string'],
                    'no_telepon' => ['nullable', 'string'],
                    'email' => ['nullable', 'email'],
                ]) + ['id_perusahaan' => $tid]
            ), 'Pelanggan'),

            'supplier' => $this->json(Supplier::create(
                $request->validate([
                    'nama_supplier' => ['required', 'string', 'max:150'],
                    'pic_kontak' => ['nullable', 'string'],
                    'no_telepon' => ['nullable', 'string'],
                    'email' => ['nullable', 'email'],
                    'alamat' => ['nullable', 'string'],
                ]) + ['id_perusahaan' => $tid]
            ), 'Supplier'),

            'merek' => $this->json(Merek::create(
                $request->validate([
                    'nama_merek' => ['required', 'string', 'max:100'],
                    'negara_asal' => ['nullable', 'string'],
                    'keterangan' => ['nullable', 'string'],
                ]) + ['id_perusahaan' => $tid]
            ), 'Merek'),

            'akun-perkiraan' => $this->storeAkunPerkiraan($request, $tid),

            'kategori-genset' => $this->json(KategoriGenset::create(
                $request->validate([
                    'kapasitas' => ['required', 'string', 'max:50'],
                    'umur_ekonomis_default' => ['required', 'integer', 'min:1'],
                    'estimasi_nilai_residu' => ['required', 'numeric', 'min:0'],
                ]) + ['id_perusahaan' => $tid]
            ), 'Kategori genset'),

            'suku-cadang' => $this->json(SukuCadang::create(
                $request->validate([
                    'nama_part' => ['required', 'string', 'max:150'],
                    'kode_sku' => ['required', 'string', 'max:50'],
                    'stok_tersedia' => ['required', 'integer', 'min:0'],
                    'harga_satuan' => ['required', 'numeric', 'min:0'],
                ]) + ['id_perusahaan' => $tid]
            ), 'Suku cadang'),

            'pengguna' => $this->storePengguna($request, $tid),

            default => response()->json(['message' => "Master '$type' tidak dikenal."], 404),
        };
    }

    /** POST /aksi/maintenance — buat work order servis baru. */
    public function storeMaintenance(Request $request)
    {
        $data = $request->validate([
            'id_genset' => ['required', 'integer'],
            'id_pengguna' => ['required', 'integer'],
            'jenis_servis' => ['required', 'in:rutin,perbaikan,overhaul'],
            'tgl_mulai_servis' => ['required', 'date'],
            'biaya_jasa_eksternal' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $wo = Pemeliharaan::create($data + [
            'id_perusahaan' => $request->user()->id_perusahaan,
            'biaya_jasa_eksternal' => $data['biaya_jasa_eksternal'] ?? 0,
        ]);

        return $this->json($wo, 'Work order servis');
    }

    protected function storeAkunPerkiraan(Request $request, int $tid)
    {
        $data = $request->validate([
            'kode_akun' => ['required', 'string', 'max:20'],
            'nama_akun' => ['required', 'string', 'max:150'],
            'kategori_akun' => ['required', 'in:aset,kewajiban,ekuitas,pendapatan,beban'],
            'sub_kategori' => ['nullable', 'string', 'max:100'],
            'saldo_normal' => ['required', 'in:debit,kredit'],
            'kode_parent' => ['nullable', 'string', 'max:20'],
        ]);

        if (AkunPerkiraan::where('id_perusahaan', $tid)->where('kode_akun', $data['kode_akun'])->exists()) {
            return response()->json(['message' => 'Kode akun sudah ada untuk perusahaan ini.'], 422);
        }

        if (! empty($data['kode_parent'])
            && ! AkunPerkiraan::where('id_perusahaan', $tid)->where('kode_akun', $data['kode_parent'])->exists()) {
            return response()->json(['message' => 'Akun induk (kode_parent) tidak ditemukan.'], 422);
        }

        $akun = AkunPerkiraan::create($data + ['id_perusahaan' => $tid, 'is_aktif' => true]);

        return $this->json($akun, 'Akun');
    }

    protected function storePengguna(Request $request, int $tid)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'role' => ['required', 'in:admin,operator,teknisi,akuntan,owner'],
            'password' => ['required', 'string', 'min:4'],
        ]);

        if (Pengguna::where('email', $data['email'])->where('id_perusahaan', $tid)->exists()) {
            return response()->json(['message' => 'Email sudah terdaftar di perusahaan ini.'], 422);
        }

        $initial = collect(explode(' ', $data['nama']))->take(2)
            ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');

        $user = Pengguna::create([
            'id_perusahaan' => $tid,
            'nama' => $data['nama'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'avatar' => $initial,
        ]);

        return $this->json($user, 'Pengguna');
    }

    /** POST /aksi/master/{type}/{id} — perbarui satu baris master data (tenant-scoped). */
    public function update(Request $request, string $type, int $id)
    {
        $tid = $request->user()->id_perusahaan;

        [$rules, $modelClass, $pk, $label] = match ($type) {
            'pelanggan' => [[
                'nama_perusahaan' => ['required', 'string', 'max:150'],
                'pic_kontak' => ['nullable', 'string'],
                'alamat_lengkap' => ['nullable', 'string'],
                'npwp' => ['nullable', 'string'],
                'no_telepon' => ['nullable', 'string'],
                'email' => ['nullable', 'email'],
            ], Pelanggan::class, 'id_pelanggan', 'Pelanggan'],

            'supplier' => [[
                'nama_supplier' => ['required', 'string', 'max:150'],
                'pic_kontak' => ['nullable', 'string'],
                'no_telepon' => ['nullable', 'string'],
                'email' => ['nullable', 'email'],
                'alamat' => ['nullable', 'string'],
            ], Supplier::class, 'id_supplier', 'Supplier'],

            'merek' => [[
                'nama_merek' => ['required', 'string', 'max:100'],
                'negara_asal' => ['nullable', 'string'],
                'keterangan' => ['nullable', 'string'],
            ], Merek::class, 'id_merek', 'Merek'],

            'suku-cadang' => [[
                'nama_part' => ['required', 'string', 'max:150'],
                'kode_sku' => ['required', 'string', 'max:50'],
                'stok_tersedia' => ['required', 'integer', 'min:0'],
                'harga_satuan' => ['required', 'numeric', 'min:0'],
            ], SukuCadang::class, 'id_part', 'Suku cadang'],

            default => [null, null, null, null],
        };

        if (! $modelClass) {
            return response()->json(['message' => "Master '$type' tidak bisa diedit."], 404);
        }

        $row = $modelClass::where('id_perusahaan', $tid)->where($pk, $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $data = $request->validate($rules);

        // Kode SKU wajib unik per perusahaan (abaikan baris ini sendiri).
        if ($type === 'suku-cadang'
            && SukuCadang::where('id_perusahaan', $tid)
                ->where('kode_sku', $data['kode_sku'])
                ->where('id_part', '!=', $id)->exists()) {
            return response()->json(['message' => 'Kode SKU sudah dipakai suku cadang lain.'], 422);
        }

        $row->update($data);

        return response()->json(['message' => $label . ' diperbarui.', 'data' => $row]);
    }

    /** POST /aksi/master/{type}/{id}/delete — hapus satu baris master data (tenant-scoped). */
    public function destroy(Request $request, string $type, int $id)
    {
        $tid = $request->user()->id_perusahaan;

        // $guard = closure yang mengembalikan pesan bila baris masih dipakai (tidak boleh dihapus).
        [$modelClass, $pk, $label, $guard] = match ($type) {
            'pelanggan' => [Pelanggan::class, 'id_pelanggan', 'Pelanggan',
                fn () => TransaksiSewa::where('id_pelanggan', $id)->exists()
                    ? 'Pelanggan masih punya transaksi sewa, tidak bisa dihapus.' : null],

            'supplier' => [Supplier::class, 'id_supplier', 'Supplier', null],

            'merek' => [Merek::class, 'id_merek', 'Merek', null],

            'suku-cadang' => [SukuCadang::class, 'id_part', 'Suku cadang',
                fn () => DetailPemeliharaan::where('id_part', $id)->exists()
                    ? 'Suku cadang sudah dipakai di work order, tidak bisa dihapus.' : null],

            default => [null, null, null, null],
        };

        if (! $modelClass) {
            return response()->json(['message' => "Master '$type' tidak bisa dihapus."], 404);
        }

        $row = $modelClass::where('id_perusahaan', $tid)->where($pk, $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        if ($guard && ($msg = $guard())) {
            return response()->json(['message' => $msg], 422);
        }

        $row->delete();

        return response()->json(['message' => $label . ' dihapus.']);
    }

    protected function json($row, string $label)
    {
        return response()->json(['message' => $label . ' tersimpan.', 'data' => $row], 201);
    }
}
