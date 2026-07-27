<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Formato de error único para TODA la API, sin importar qué
        // controlador o middleware haya lanzado la excepción.
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null; // deja que Laravel maneje las rutas web normalmente
            }

            $status = 500;
            $message = 'Ocurrió un error interno en el servidor.';
            $errors = null;

            if ($e instanceof Illuminate\Validation\ValidationException) {
                $status = 422;
                $message = 'Los datos enviados no son válidos.';
                $errors = $e->errors();
            } elseif ($e instanceof Illuminate\Auth\AuthenticationException) {
                $status = 401;
                $message = 'No autenticado. Envía un token válido en el header Authorization.';
            } elseif ($e instanceof Illuminate\Auth\Access\AuthorizationException
                || $e instanceof Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                $status = 403;
                $message = $e->getMessage() ?: 'No tienes permisos para realizar esta acción.';
            } elseif ($e instanceof Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                $status = 404;
                $message = 'El recurso solicitado no existe.';
            } elseif ($e instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: $message;
            }

            $body = ['message' => $message];
            if ($errors !== null) {
                $body['errors'] = $errors;
            }
            if (config('app.debug') && $status === 500) {
                $body['exception'] = get_class($e);
                $body['debug_message'] = $e->getMessage();
            }

            return response()->json($body, $status);
        });
    })->create();
