<?php

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
            'installed' => \App\Http\Middleware\EnsureInstalled::class,
            'not.installed' => \App\Http\Middleware\EnsureNotInstalled::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureInstalled::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
