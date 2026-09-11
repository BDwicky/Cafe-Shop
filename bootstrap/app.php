<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Guest gate untuk semua halaman /kasir diarahkan ke login kasir
        $middleware->redirectGuestsTo(fn () => route('kasir.login'));

        // Pengecualian CSRF untuk request musik struk publik & sinkronisasi audio realtime
        $middleware->validateCsrfTokens(except: [
            'music/request',
            'music/validate-code',
            'kasir/music/playback-sync',
            'kasir/music/master-host/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
