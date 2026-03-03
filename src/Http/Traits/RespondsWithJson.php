<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Responds With JSON Trait.
 *
 * Provides JSON response helpers for controllers.
 */
trait RespondsWithJson
{
    /**
     * Return a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string, string> $headers
     * @return JsonResponse
     */
    protected function jsonResponse(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $status, $headers);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param mixed $paginator
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse(mixed $paginator, string $message = 'Success'): JsonResponse
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return a created response (201).
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function createdResponse(mixed $data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    /**
     * Return a no content response (204).
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return $this->jsonResponse(null, 204);
    }

    /**
     * Return a validation error response (422).
     *
     * @param array<string, mixed> $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return a not found response (404).
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Return an unauthorized response (401).
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Return a forbidden response (403).
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
