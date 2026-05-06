<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;


class ApiHandlerException extends Exception
{

    public function __invoke(Throwable $e, Request $request)
    {

        if ($request->is('api/*') || $request->expectsJson()) {
            Log::error('API Exception Caught', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return api_error('Resource not found', 404);
            }
            if ($e instanceof MethodNotAllowedHttpException) {
                return api_error('Method not allowed', 405);
            }
            if ($e instanceof AuthenticationException) {
                return api_error('Unauthenticated', 401);
            }
            if ($e instanceof AuthorizationException) {
                return api_error('Forbidden', 403);
            }
            if ($e instanceof ThrottleRequestsException) {
                return api_error('Too many requests', 429);
            }
            if ($e instanceof QueryException) {
                return api_error('Database error', 500, $e->getMessage());
            }
            if ($e instanceof ValidationException) {
                return api_validation_error($e->errors(), "failed validation", 422);
            }


            return api_error('Server error', 500);
        }
        return null;
    }
}
