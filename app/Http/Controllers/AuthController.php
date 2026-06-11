<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Models\Perusahaan;
use App\Support\VoltraData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Autentikasi Voltra — kredensial divalidasi terhadap tabel `pengguna`.
 * Email global dapat terdaftar di beberapa tenant (multi-tenant SaaS); tenant
 * tujuan dipilih PASCA verifikasi password.
 */
class AuthController extends Controller
{
    private const SESSION_TENANT_CANDIDATES = 'tenant_candidates';

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $matches = Pengguna::where('email', $cred['email'])->get()
            ->filter(fn ($u) => Hash::check($cred['password'], $u->password));

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if ($matches->count() === 1) {
            return $this->completeLogin($request, $matches->first());
        }

        $request->session()->put(
            self::SESSION_TENANT_CANDIDATES,
            $matches->pluck('id_pengguna')->all()
        );

        return redirect()->route('tenant.pick');
    }

    public function showRegister()
    {
        // Daftar perusahaan TIDAK diekspos ke publik (privasi multi-tenant).
        // Bergabung ke perusahaan lain memakai kode undangan dari owner/admin.
        return view('pages.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'mode' => ['required', Rule::in(['new', 'join'])],
            'nama_perusahaan' => ['required_if:mode,new', 'nullable', 'string', 'max:150'],
            'kode_undangan' => ['required_if:mode,join', 'nullable', 'string', 'max:12'],
        ]);

        // Validasi kode undangan di luar closure agar pesan errornya jelas.
        $target = null;
        if ($data['mode'] === 'join') {
            $target = Perusahaan::where('kode_undangan', strtoupper(trim($data['kode_undangan'])))
                ->where('status_aktif', true)
                ->first();

            if (! $target) {
                throw ValidationException::withMessages([
                    'kode_undangan' => 'Kode undangan tidak dikenal. Minta kode dari owner/admin perusahaan Anda.',
                ]);
            }
        }

        DB::connection('voltra')->transaction(function () use ($data, $target) {
            if ($data['mode'] === 'new') {
                $perusahaan = Perusahaan::create([
                    'nama_perusahaan' => $data['nama_perusahaan'],
                    'status_aktif' => true,
                    'tgl_bergabung' => now()->toDateString(),
                    'kode_undangan' => strtoupper(Str::random(8)),
                ]);
                $idPerusahaan = $perusahaan->id_perusahaan;
                $role = 'owner';
                $this->seedDefaultCoa($idPerusahaan);
            } else {
                $idPerusahaan = $target->id_perusahaan;
                $role = 'operator';
            }

            $duplicate = Pengguna::where('email', $data['email'])
                ->where('id_perusahaan', $idPerusahaan)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'email' => 'Email ini sudah terdaftar di perusahaan tersebut.',
                ]);
            }

            Pengguna::create([
                'id_perusahaan' => $idPerusahaan,
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'avatar' => strtoupper(mb_substr($data['nama'], 0, 2)),
            ]);
        });

        return redirect()->route('login')->with('status', 'Registrasi berhasil. Silakan masuk.');
    }

    public function showTenantPicker(Request $request)
    {
        $ids = $request->session()->get(self::SESSION_TENANT_CANDIDATES, []);

        if (empty($ids)) {
            return redirect()->route('login');
        }

        $candidates = Pengguna::with('perusahaan')->whereIn('id_pengguna', $ids)->get();

        return view('pages.tenant-pick', compact('candidates'));
    }

    public function pickTenant(Request $request)
    {
        $ids = $request->session()->get(self::SESSION_TENANT_CANDIDATES, []);

        if (empty($ids)) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'id_pengguna' => ['required', 'integer', Rule::in($ids)],
        ]);

        $user = Pengguna::findOrFail($data['id_pengguna']);
        $request->session()->forget(self::SESSION_TENANT_CANDIDATES);

        return $this->completeLogin($request, $user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function completeLogin(Request $request, Pengguna $user)
    {
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Seed Chart of Accounts standar Voltra untuk perusahaan yang baru dibuat,
     * supaya jurnal manual (mis. setoran modal awal) langsung bisa dipakai.
     */
    private function seedDefaultCoa(int $idPerusahaan): void
    {
        $rows = array_map(
            fn ($a) => array_merge($a, ['id_perusahaan' => $idPerusahaan]),
            VoltraData::akunPerkiraan()
        );

        DB::connection('voltra_akuntansi')->table('akun_perkiraan')->insert($rows);
    }
}
