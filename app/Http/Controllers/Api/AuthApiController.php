<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * API auth — token-based (Laravel Sanctum).
 */
class AuthApiController extends Controller
{
    /** POST /api/login → terbitkan token. */
    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'id_perusahaan' => ['required', 'integer'],
        ]);

        $user = Pengguna::where('email', $cred['email'])
            ->where('id_perusahaan', $cred['id_perusahaan'])
            ->first();

        if (! $user || ! Hash::check($cred['password'], $user->password)) {
            return response()->json(['message' => 'Email, perusahaan, atau password salah.'], 401);
        }

        return response()->json([
            'token' => $user->createToken('voltra-api')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /** GET /api/me → profil pengguna saat ini. */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('perusahaan'));
    }

    /** POST /api/logout → cabut token aktif. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token dicabut.']);
    }
}
