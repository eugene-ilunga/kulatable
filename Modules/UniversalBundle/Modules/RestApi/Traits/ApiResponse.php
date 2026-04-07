<?php

namespace Modules\RestApi\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API response trait for consistent JSON responses.
 * 
 * Usage in Controllers:
 * ```php
 * use Modules\RestApi\Traits\ApiResponse;
 * 
 * class MyController
 * {
 *     use ApiResponse;
 *     
 *     public function index()
 *     {
 *         return $this->successResponse($data, 'Records fetched successfully');
 *     }
 * }
 * ```
 */
trait ApiResponse
{
    /**
     * Return a success response.
     *
     * @param mixed  $data    The data to return
     * @param string $message Optional success message
     * @param int    $code    HTTP status code (default: 200)
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error response.
     *
     * @param string $message Error message
     * @param int    $code    HTTP status code (default: 400)
     * @param mixed  $errors  Optional validation errors or additional info
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response.
     *
     * @param string $message Optional custom message
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return an unauthorized response.
     *
     * @param string $message Optional custom message
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a forbidden response.
     *
     * @param string $message Optional custom message
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a validation error response.
     *
     * @param array|object $errors Validation errors
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }

    /**
     * Return a server error response.
     *
     * @param string          $message   Error message
     * @param \Throwable|null $exception Optional exception for logging
     */
    protected function serverErrorResponse(string $message = 'Internal server error', ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            \Log::error('API Server Error', [
                'message' => $message,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return $this->errorResponse($message, 500);
    }

    /**
     * Return a created response (201).
     *
     * @param mixed  $data    The created resource data
     * @param string $message Optional success message
     */
    protected function createdResponse($data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response (204).
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a paginated response.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message Optional success message
     */
    protected function paginatedResponse($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], 200);
    }
}
