<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Resources\Api\V1\ErrorResource;
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

                if ($e instanceof ValidationException) {
                    return ErrorResource::validation($e->errors());
                }

                if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    return ErrorResource::make(
                        'Not Found',
                        'The resource you requested was not found.',
                        Response::HTTP_NOT_FOUND,
                        'NOT_FOUND'
                    );
                }

                if ($e instanceof AuthenticationException) {
                    return ErrorResource::make(
                        'Unauthenticated',
                        'You must be logged in to access this resource.',
                        Response::HTTP_UNAUTHORIZED,
                        'UNAUTHENTICATED'
                    );
                }

                // Tambahan: handle Gate::authorize() yang lempar 403
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return ErrorResource::make(
                        'Forbidden',
                        'You do not have permission to perform this action.',
                        Response::HTTP_FORBIDDEN,
                        'FORBIDDEN'
                    );
                }

                $statusCode = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR;

                return ErrorResource::make(
                    $statusCode === 500 ? 'Internal Server Error' : 'Error',
                    config('app.debug') ? $e->getMessage() : 'An error occurred on the server.',
                    $statusCode,
                    null,
                    $e
                );
            }
        });
    })->create();
