<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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

        // 1. Invalid inputs / validation errors
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid input data.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 2. Authentication failure / invalid or missing token
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authentication failed. Please login first.',
                ], 401);
            }
        });

        // 3. Authorization failure / permission denied
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }
        });

        // 4. Database errors
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                $errorCode = $e->errorInfo[1] ?? null;

                if ($errorCode == 1062) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Duplicate data found. This record already exists.',
                    ], 409);
                }

                return response()->json([
                    'status' => false,
                    'message' => 'A database error occurred. Please try again later.',
                ], 500);
            }
        });

        // 5. External API / service connection errors
        $exceptions->render(function (ConnectionException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'External service is temporarily unavailable. Please try again later.',
                ], 503);
            }
        });

        // 6. Invalid API route
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'API endpoint not found.',
                ], 404);
            }
        });

        // 7. Wrong HTTP method
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'HTTP method not allowed for this endpoint.',
                ], 405);
            }
        });

        // 8. General fallback error
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Something went wrong. Please try again later.',
                ], 500);
            }
        });

    })->create();