<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pembatas akses berdasarkan role pengguna.
 * Contoh pemakaian di route: ->middleware('role:owner,akuntan')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! empty($roles) && ! in_array($user->role, $roles, true))) {
            abort(403, 'Akses ditolak untuk role Anda.');
        }

        return $next($request);
    }
}
