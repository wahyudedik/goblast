<?php

use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Middleware\FeatureMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases for route usage
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
            'role' => RoleMiddleware::class,
            'feature' => FeatureMiddleware::class,
            'api.token' => ApiTokenMiddleware::class,
        ]);

        // Exclude webhook routes from CSRF protection
        $middleware->preventRequestForgery(except: [
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
