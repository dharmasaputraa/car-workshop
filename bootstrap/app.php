<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureUserIsActive::class
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {

            if ($request->is('api/*') || $request->expectsJson()) {

                /* 1. Handle Validation Exception (422) */
                if ($e instanceof ValidationException) {
                    $errors = [];
                    foreach ($e->errors() as $field => $messages) {
                        foreach ($messages as $message) {
                            $errors[] = [
                                'status' => (string) Response::HTTP_UNPROCESSABLE_ENTITY,
                                'title' => 'Validation Error',
                                'detail' => $message,
                                'source' => ['pointer' => "/data/attributes/{$field}"],
                            ];
                        }
                    }
                    return response()->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                /* 2. Handle Not Found (404) */
                if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
                    return response()->json([
                        'errors' => [
                            [
                                'status' => (string) Response::HTTP_NOT_FOUND,
                                'title' => 'Not Found',
                                'detail' => 'The resource you requested was not found.'
                            ]
                        ]
                    ], Response::HTTP_NOT_FOUND);
                }

                /* 3. Handle Unauthorized (401) */
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'errors' => [
                            [
                                'status' => (string) Response::HTTP_UNAUTHORIZED,
                                'title' => 'Unauthenticated',
                                'detail' => 'Anda harus login untuk mengakses resource ini.'
                            ]
                        ]
                    ], Response::HTTP_UNAUTHORIZED);
                }

                /* 4. Handle General Server Error / Custom HTTP Exception */
                // CARA BARU YANG AMAN:
                // Cek apakah error ini implement HttpExceptionInterface milik Symfony
                $statusCode = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR;

                return response()->json([
                    'errors' => [
                        [
                            'status' => (string) $statusCode,
                            'title' => $statusCode === 500 ? 'Internal Server Error' : 'Error',
                            // Hanya tampilkan detail error asli jika APP_DEBUG=true (demi keamanan)
                            'detail' => config('app.debug') ? $e->getMessage() : 'An error occurred on the server.'
                        ]
                    ]
                ], $statusCode);
            }
        });
    })->create();
