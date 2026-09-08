<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\LanguageMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ الميدل وير الأساسية
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Session\Middleware\StartSession::class,
            LanguageMiddleware::class,
        ]);

        // ✅ استثناءات CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // ✅ أسماء مستعارة للميدل وير
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
