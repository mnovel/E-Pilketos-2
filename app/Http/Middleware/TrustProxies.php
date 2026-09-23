<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * ✅ Trust semua proxy (Traefik + Nginx)
     *
     * Setup: Traefik (reverse proxy) → Nginx (web server) → Laravel
     *
     * Kalau mau lebih ketat, bisa sebutkan IP spesifik:
     * protected $proxies = [
     *     '127.0.0.1',        // Nginx local
     *     '10.10.1.1',        // Traefik internal
     *     '10.10.1.0/24',     // Subnet Docker/internal
     * ];
     */
    protected $proxies = '*';

    /**
     * ✅ Header yang dikirim proxy untuk deteksi IP asli & protokol
     */
    protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
