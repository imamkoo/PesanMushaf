<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Render berjalan di belakang Cloudflare, yang selalu menimpa CF-Connecting-IP
 * dengan IP klien asli. X-Forwarded-For bisa diisi bebas oleh klien, sehingga
 * rate limit per-IP dapat dilewati. Bila header edge valid, jadikan itu IP klien.
 */
class UseEdgeClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $edgeIp = trim((string) $request->headers->get('CF-Connecting-IP', ''));

        if ($edgeIp !== '' && filter_var($edgeIp, FILTER_VALIDATE_IP) !== false) {
            $request->server->set('REMOTE_ADDR', $edgeIp);
            $request->server->remove('HTTP_X_FORWARDED_FOR');
            $request->headers->remove('X-Forwarded-For');
        }

        return $next($request);
    }
}
