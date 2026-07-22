<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Form request base with a consistent JSON error envelope and a couple of
 * conveniences for merging extra data into the validated payload.
 */
abstract class ArchitectFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * Validated data merged with extra key/value pairs.
     *
     * @return array<string, mixed>
     */
    public function validatedWith(array $extra): array
    {
        return array_merge($this->validated(), $extra);
    }

    /**
     * A subset of the validated data.
     *
     * @return array<string, mixed>
     */
    public function validatedOnly(array $keys): array
    {
        return array_intersect_key($this->validated(), array_flip($keys));
    }

    protected function failedValidation(Validator $validator): void
    {
        if (! $this->expectsJson()) {
            parent::failedValidation($validator);
        }

        $keys = config('lara-architect.responses.keys', []);

        throw new HttpResponseException(response()->json([
            $keys['status'] ?? 'status' => 'error',
            $keys['message'] ?? 'message' => 'The given data was invalid.',
            $keys['errors'] ?? 'errors' => $validator->errors()->toArray(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
