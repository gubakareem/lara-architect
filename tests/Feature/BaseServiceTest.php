<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use KarimAshraf\LaraArchitect\Tests\Concerns\CreatesPostsTable;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostService;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class BaseServiceTest extends TestCase
{
    use CreatesPostsTable;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPostsTable();
        $this->service = $this->app->make(PostService::class);
    }

    public function test_create_applies_prepare_hook_and_fires_created_hook(): void
    {
        $post = $this->service->create(['title' => 'hello world']);

        $this->assertSame('Hello World', $post->title, 'prepareForCreate should have title-cased the input');
        $this->assertSame(['created:'.$post->id], $this->service->events);
    }

    public function test_update_and_delete_fire_hooks(): void
    {
        $post = $this->service->create(['title' => 'hello']);

        $this->service->update($post->id, ['title' => 'Changed']);
        $this->service->delete($post->id);

        $this->assertSame('Changed', $post->fresh()->title);
        $this->assertContains('updated:'.$post->id, $this->service->events);
        $this->assertContains('deleted', $this->service->events);
    }

    public function test_find_or_fail_throws_for_missing_records(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->findOrFail(999);
    }

    public function test_it_exposes_the_repository(): void
    {
        $this->assertSame(0, $this->service->repository()->query()->count());
    }
}
