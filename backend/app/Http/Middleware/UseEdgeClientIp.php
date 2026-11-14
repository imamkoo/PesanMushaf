<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menentukan IP klien asli untuk rate limit.
 *
 * X-Forwarded-For bisa diisi bebas oleh klien, jadi header itu SELALU dibuang.
 * CF-Connecting-IP hanya dipercaya bila TRUST_CF_CONNECTING_IP aktif (default),
 * karena Cloudflare menimpanya di edge. Di hosting langsung (cPanel/LiteSpeed
 * tanpa Cloudflare) set TRUST_CF_CONNECTING_IP=false agar header itu tidak bisa
 * dipalsukan untuk melewati throttle per-IP.
 */
class UseEdgeClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->remove('X-Forwarded-For');
        $request->server->remove('HTTP_X_FORWARDED_FOR');

        if (config('app.trust_cf_connecting_ip')) {
            $edgeIp = trim((string) $request->headers->get('CF-Connecting-IP', ''));

            if ($edgeIp !== '' && filter_var($edgeIp, FILTER_VALIDATE_IP) !== false) {
                $request->server->set('REMOTE_ADDR', $edgeIp);
            }
        }

        return $next($request);
    }
}
