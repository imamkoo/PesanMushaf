<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil key dari Header 'X-API-KEY'
        $apiKey = $request->header('X-API-KEY');

        // 2. Validasi: Jika key tidak ada atau tidak ditemukan di database
        if (!$apiKey || !ApiKey::where('key', $apiKey)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: API Key is invalid or missing.'
            ], 401);
        }

        return $next($request);
    }
}