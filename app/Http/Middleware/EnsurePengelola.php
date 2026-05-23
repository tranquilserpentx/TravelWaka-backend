<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePengelola
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role !== 'pengelola' && $request->user()->role !== 'super_admin') {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Hanya pengelola yang bisa mengakses fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}