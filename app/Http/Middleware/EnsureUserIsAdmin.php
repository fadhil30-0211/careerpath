<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'data' => null,
                'message' => 'Anda tidak memiliki akses Admin.',
                'errors' => ['role' => ['Akses ditolak.']],
                'meta' => ['request_id' => (string) Str::uuid()],
            ], 403);
        }

        return $next($request);
    }
}
