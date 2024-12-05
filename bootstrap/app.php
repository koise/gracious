<?php

use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\AdminGuestMiddleware;
use App\Http\Middleware\UserAuthMiddleware;
use App\Http\Middleware\UserGuestMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful; // Add this

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'admin.guest' => AdminGuestMiddleware::class,
            'admin.auth' => AdminAuthMiddleware::class,
            'user.guest' => UserGuestMiddleware::class,
            'user.auth' => UserAuthMiddleware::class,
            'preventBackHistory' => PreventBackHistory::class,
            'csrf.disabled' => \App\Http\Middleware\DisableCsrfProtection::class,
        ]);

        $middleware->redirectTo();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();