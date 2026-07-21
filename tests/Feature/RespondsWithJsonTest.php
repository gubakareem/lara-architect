<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use KarimAshraf\LaraArchitect\Tests\Concerns\CreatesPostsTable;
use KarimAshraf\LaraArchitect\Tests\Fixtures\Post;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostController;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class RespondsWithJsonTest extends TestCase
{
    use CreatesPostsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPostsTable();
    }

    protected function defineRoutes($router): void
    {
        $router->post('/posts', [PostController::class, 'store']);
        $router->delete('/posts/{post}', [PostController::class, 'destroy'])
            ->middleware(SubstituteBindings::class);
    }

    public function test_created_responses_use_the_success_envelope(): void
    {
        $response = $this->postJson('/posts', ['title' => 'hello world']);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Resource created successfully.')
            ->assertJsonPath('data.title', 'Hello World');
    }

    public function test_deleted_responses_use_the_success_envelope(): void
    {
        $post = Post::create(['title' => 'Bye']);

        $this->deleteJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_envelope_keys_are_configurable(): void
    {
        config()->set('lara-architect.responses.keys', [
            'status' => 'state',
            'message' => 'msg',
            'data' => 'payload',
            'errors' => 'problems',
        ]);

        $this->postJson('/posts', ['title' => 'hello'])
            ->assertCreated()
            ->assertJsonPath('state', 'success')
            ->assertJsonPath('payload.title', 'Hello');

        $this->postJson('/posts', [])
            ->assertStatus(422)
            ->assertJsonPath('state', 'error')
            ->assertJsonStructure(['problems' => ['title']]);
    }
}
