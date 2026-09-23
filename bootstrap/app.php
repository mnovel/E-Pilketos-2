<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SchoolNetworkMiddleware;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ✅ TrustProxies dipanggil paling atas
        $middleware->prepend(TrustProxies::class);

        $middleware->alias([
            'role'           => RoleMiddleware::class,
            // 'school.network' => SchoolNetworkMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
