<?php

use App\Exceptions\CouponValidationException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ShippingProviderException;
use App\Http\Middleware\EnsureAdminUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'admin' => EnsureAdminUser::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'message' => 'Unauthenticated.', 'errors' => (object) []], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'message' => 'You are not authorized to perform this action.', 'errors' => (object) []], 403);
        });

        $exceptions->render(function (InsufficientStockException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'message' => $exception->getMessage(), 'errors' => (object) []], 409);
        });

        $exceptions->render(function (CouponValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'code' => $exception->reasonCode, 'message' => $exception->getMessage(), 'errors' => (object) []], 422);
        });

        $exceptions->render(function (ShippingProviderException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'code' => $exception->reasonCode, 'message' => $exception->getMessage(), 'errors' => (object) []], 422);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'message' => 'Resource not found.', 'errors' => (object) []], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            $message = match ($status) {
                404 => 'API endpoint not found.',
                429 => 'Too many requests.',
                403 => 'You are not authorized to perform this action.',
                default => $status >= 500 ? 'An unexpected server error occurred.' : ($exception->getMessage() ?: 'Unable to process request.'),
            };

            return response()->json(['success' => false, 'message' => $message, 'errors' => (object) []], $status);
        });
    })->create();
