<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Penggunaan di route:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,operator')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role->value;

        if (!in_array($userRole, $roles, true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki role yang sesuai.');
        }

        return $next($request);
    }
}
