<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies extends Middleware
{
    /**
     * ✅ Trust semua proxy (Cloudflare → Traefik → Nginx)
     */
    protected $proxies = '*';

    /**
     * ✅ Header standar untuk deteksi IP & protokol asli
     */
    protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;

    /**
     * ✅ Override handle(): baca CF-Connecting-IP sebelum parent process.
     *
     * Cloudflare mengirim CF-Connecting-IP berisi IP asli user.
     * Kita set REMOTE_ADDR ke IP itu supaya rate limiter & log pakai IP asli.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cfIp = $request->header('CF-Connecting-IP');

        if ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            // Set IP asli user sebagai REMOTE_ADDR
            $request->server->set('REMOTE_ADDR', $cfIp);

            // Set juga di Symfony request biar konsisten
            $request->overrideGlobals();
        }

        // ✅ Baru panggil parent untuk handle X-Forwarded-*
        return parent::handle($request, $next);
    }
}
