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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin.role' => \App\Http\Middleware\AdminRole::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'vendor' => \App\Http\Middleware\VendorMiddleware::class,
            'driver' => \App\Http\Middleware\DriverMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
        
        // Add SetLocale and CheckInstalled middleware to web group
        $middleware->web(append: [
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ApplyTheme::class,
        ]);
        
        // Exclude Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
        
        // Exclude locale cookies from encryption so they can be read properly
        $middleware->encryptCookies(except: [
            'locale',
            'locale_version',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
