<?php

if (!function_exists('api_success')) {
    /**
     * Standard success response for API.
     *
     * Usage:
     *   api_success($data);
     *   api_success($data, 'Optional message');
     *   api_success($data, 'Optional message', 201);
     */
    function api_success($data = null, string $message = "Operation completed successfully", int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}

if (!function_exists('api_error')) {
    function api_error(string $message = "Operation failed", int $code = 400, $error = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], $code);
    }
}
if (!function_exists('api_paginate')) {
    /**
     * Standard paginated response.
     */
    function api_paginate($resource, string $message = "Operation completed successfully", int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource->items(),
            'meta' => [
                'current_page' => $resource->currentPage(),
                'total' => $resource->total(),
                'per_page' => $resource->perPage(),
                'last_page' => $resource->lastPage(),
            ],
        ], $code);
    }
}

if (!function_exists('api_validation_error')) {
    function api_validation_error($errors, string $message = "Validation failed", int $code = 422)
    {
        $formattedErrors = [];

        if (is_array($errors)) {
            foreach ($errors as $field => $messages) {
                $formattedErrors[] = [
                    'field' => $field,
                    'error' => is_array($messages) ? ($messages[0] ?? "invalid value") : $messages,
                ];
            }
        } else {
            $formattedErrors[] = ['error' => $errors];
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $formattedErrors,
        ], $code);
    }
}
