<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $status = 500;
            $message = config('app.debug') ? $e->getMessage() : 'An error occurred.';
            $errors = null;

            if ($e instanceof ValidationException) {
                $status = 422;
                $message = $e->getMessage();
                $errors = $e->errors();
            } elseif ($e instanceof AuthenticationException) {
                $status = 401;
                $message = $e->getMessage() ?: 'Unauthenticated.';
            } elseif ($e instanceof JWTException) {
                $status = 401;
                $message = $e->getMessage() ?: 'Invalid or expired token.';
            } elseif ($e instanceof QueryException) {
                $status = 422;
                $message = 'The given data is invalid.';
                if (str_contains($e->getMessage(), 'Duplicate') && str_contains($e->getMessage(), 'email')) {
                    $errors = ['email' => ['This email is already registered.']];
                }
            } elseif ($e instanceof HttpException) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: 'Request failed.';
            }

            return response()->json(array_filter([
                'message' => $message,
                'errors' => $errors,
            ]), $status);
        });
    })->create();
