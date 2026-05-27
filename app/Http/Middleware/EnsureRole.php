<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-Based Access Control (Bab 3.4.2 TA).
 * Pemakaian: ->middleware('role:owner,akuntan')
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
