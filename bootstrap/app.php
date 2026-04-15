<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Load WSL-specific environment overrides if running in WSL
if (file_exists(__DIR__.'/../.env.wsl') &&
    file_exists('/proc/version') &&
    str_contains(file_get_contents('/proc/version'), 'microsoft')) {

    $wslEnv = file_get_contents(__DIR__.'/../.env.wsl');
    foreach (explode("\n", $wslEnv) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhook/chip',
            'webhook/enrollment/chip',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'enrolled' => \App\Http\Middleware\EnsureEnrolled::class,
            'not.suspended' => \App\Http\Middleware\EnsureNotSuspended::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
