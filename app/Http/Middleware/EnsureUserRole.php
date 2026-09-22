<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('kasir.login');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            $allowedRoleNames = implode('/', array_map('ucfirst', $roles));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Akses ditolak: Fitur ini hanya dapat diakses oleh {$allowedRoleNames}.",
                ], 403);
            }

            return redirect()->route('kasir.terminal')
                ->with('alert', "Akses ditolak: Halaman ini hanya dapat diakses oleh {$allowedRoleNames}.");
        }

        return $next($request);
    }
}
