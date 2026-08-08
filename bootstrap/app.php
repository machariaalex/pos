<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);

        // Render terminates TLS at its edge and forwards to this container
        // over plain HTTP, so without this Laravel never sees the request as
        // HTTPS — it trusted no proxy's X-Forwarded-Proto header, generated
        // http:// asset/URL links on an https:// page, and browsers blocked
        // them as mixed content. The container itself isn't reachable except
        // through Render's own edge, so trusting all proxies here is safe.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
