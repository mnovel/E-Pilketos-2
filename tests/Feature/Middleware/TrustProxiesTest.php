<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Bikin Request dengan header CF-Connecting-IP.
     */
    protected function makeRequest(?string $cfIp = null, array $server = []): Request
    {
        $request = Request::create('/', 'GET', [], [], [], $server);

        if ($cfIp !== null) {
            $request->headers->set('CF-Connecting-IP', $cfIp);
        }

        return $request;
    }

    /**
     * Handle request lewat middleware, return request yang sudah diproses.
     */
    protected function passThrough(Request $request): Request
    {
        $middleware = new TrustProxies();

        $middleware->handle($request, function () {
            return response()->json(['ok' => true]);
        });

        return $request;
    }

    // =========================================================
    // CF-Connecting-IP
    // =========================================================

    public function test_sets_remote_addr_from_cf_connecting_ip()
    {
        $request = $this->makeRequest('203.0.113.42');

        $this->passThrough($request);

        $this->assertSame('203.0.113.42', $request->server->get('REMOTE_ADDR'));
    }

    public function test_request_ip_returns_cf_ip()
    {
        $request = $this->makeRequest('203.0.113.42');

        $this->passThrough($request);

        $this->assertSame('203.0.113.42', $request->ip());
    }

    public function test_supports_ipv6_cf_header()
    {
        $request = $this->makeRequest('2001:db8::1');

        $this->passThrough($request);

        $this->assertSame('2001:db8::1', $request->ip());
    }

    // =========================================================
    // INVALID HEADER — FALLBACK
    // =========================================================

    public function test_invalid_cf_ip_falls_back_to_remote_addr()
    {
        $request = $this->makeRequest('not-an-ip', [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->passThrough($request);

        // Tetap 192.168.1.1 karena CF header invalid
        $this->assertSame('192.168.1.1', $request->server->get('REMOTE_ADDR'));
    }

    public function test_empty_cf_ip_falls_back_to_remote_addr()
    {
        $request = $this->makeRequest('', [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->passThrough($request);

        $this->assertSame('192.168.1.1', $request->server->get('REMOTE_ADDR'));
    }

    public function test_no_cf_header_keeps_remote_addr()
    {
        $request = $this->makeRequest(null, [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->passThrough($request);

        $this->assertSame('192.168.1.1', $request->server->get('REMOTE_ADDR'));
    }

    public function test_private_ip_cf_header_is_respected()
    {
        // TrustProxies tidak memvalidasi apakah IP "public" atau "private"
        // Kalau CF kirim private IP, middleware tetap pakai
        $request = $this->makeRequest('127.0.0.1', [
            'REMOTE_ADDR' => '203.0.113.1',
        ]);

        $this->passThrough($request);

        $this->assertSame('127.0.0.1', $request->server->get('REMOTE_ADDR'));
    }

    // =========================================================
    // X-FORWARDED-* (parent behavior)
    // =========================================================

    public function test_trusts_x_forwarded_for()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR'           => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR'  => '203.0.113.99',
        ]);

        $this->passThrough($request);

        // Parent TrustProxies akan baca X-Forwarded-For
        $this->assertSame('203.0.113.99', $request->ip());
    }

    // =========================================================
    // INTEGRATION
    // =========================================================

    public function test_cf_header_overrides_x_forwarded_for()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR'           => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR'  => '203.0.113.99',
        ]);
        $request->headers->set('CF-Connecting-IP', '198.51.100.1');

        $this->passThrough($request);

        // CF-Connecting-IP lebih diprioritaskan (set REMOTE_ADDR langsung)
        $this->assertSame('198.51.100.1', $request->server->get('REMOTE_ADDR'));
    }

    public function test_middleware_returns_response()
    {
        $request = $this->makeRequest('203.0.113.42');

        $middleware = new TrustProxies();

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        });

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
