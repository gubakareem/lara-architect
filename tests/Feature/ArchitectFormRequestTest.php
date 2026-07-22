<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use KarimAshraf\LaraArchitect\Tests\Fixtures\StorePostRequest;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class ArchitectFormRequestTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('/posts', fn (StorePostRequest $request) => response()->json([
            'ok' => true,
            'validated' => $request->validatedWith(['extra' => 'value']),
        ]));
    }

    public function test_json_requests_get_a_consistent_error_envelope(): void
    {
        $response = $this->postJson('/posts', ['body' => 'no title']);

        $response
            ->assertStatus(422)
            ->assertJson(['status' => 'error', 'message' => 'The given data was invalid.'])
            ->assertJsonStructure(['status', 'message', 'errors' => ['title']]);
    }

    public function test_valid_input_passes_and_validated_with_merges_extras(): void
    {
        $response = $this->postJson('/posts', ['title' => 'Hello']);

        $response
            ->assertOk()
            ->assertJsonPath('validated.title', 'Hello')
            ->assertJsonPath('validated.extra', 'value');
    }
}
