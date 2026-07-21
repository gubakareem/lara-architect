<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Http\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Consistent JSON envelope for controllers. Key names are configurable via
 * config('lara-architect.responses.keys').
 */
trait RespondsWithJson
{
    protected function respondSuccess(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $keys = $this->responseKeys();

        $payload = [$keys['status'] => 'success'];

        if ($message !== null) {
            $payload[$keys['message']] = $message;
        }

        if ($data !== null) {
            $payload[$keys['data']] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function respondCreated(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->respondSuccess($data, $message ?? 'Resource created successfully.', 201);
    }

    protected function respondDeleted(?string $message = null): JsonResponse
    {
        return $this->respondSuccess(null, $message ?? 'Resource deleted successfully.');
    }

    protected function respondError(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $keys = $this->responseKeys();

        $payload = [
            $keys['status'] => 'error',
            $keys['message'] => $message,
        ];

        if ($errors !== null) {
            $payload[$keys['errors']] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * @return array{status: string, message: string, data: string, errors: string}
     */
    private function responseKeys(): array
    {
        $keys = config('lara-architect.responses.keys', []);

        return [
            'status' => $keys['status'] ?? 'status',
            'message' => $keys['message'] ?? 'message',
            'data' => $keys['data'] ?? 'data',
            'errors' => $keys['errors'] ?? 'errors',
        ];
    }
}
