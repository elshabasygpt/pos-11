<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Presentation\Middleware\TenantMiddleware;
use App\Presentation\Middleware\SubscriptionActiveMiddleware;
use App\Presentation\Middleware\RolePermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
            'subscription.active' => SubscriptionActiveMiddleware::class,
            'role' => RolePermissionMiddleware::class,
            'partner.auth' => \App\Http\Middleware\PartnerAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        });
    })->create();
