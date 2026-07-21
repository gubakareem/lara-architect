<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use KarimAshraf\LaraArchitect\Exceptions\SoftDeletesNotEnabledException;
use KarimAshraf\LaraArchitect\Tests\Concerns\CreatesPostsTable;
use KarimAshraf\LaraArchitect\Tests\Fixtures\Post;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostRepository;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostService;
use KarimAshraf\LaraArchitect\Tests\Fixtures\Tag;
use KarimAshraf\LaraArchitect\Tests\Fixtures\TagRepository;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class SoftDeleteOperationsTest extends TestCase
{
    use CreatesPostsTable;

    private PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPostsTable();
        $this->createTagsTable();
        $this->repository = new PostRepository;
    }

    public function test_delete_many_soft_deletes_the_given_ids(): void
    {
        $posts = collect(['A', 'B', 'C'])->map(fn (string $t) => Post::create(['title' => $t]));

        $deleted = $this->repository->deleteMany([$posts[0]->id, $posts[1]->id]);

        $this->assertSame(2, $deleted);
        $this->assertSoftDeleted('posts', ['id' => $posts[0]->id]);
        $this->assertSoftDeleted('posts', ['id' => $posts[1]->id]);
        $this->assertSame(1, Post::count());
        $this->assertSame(0, $this->repository->deleteMany([]));
    }

    public function test_delete_all_soft_deletes_everything(): void
    {
        Post::create(['title' => 'A']);
        Post::create(['title' => 'B']);

        $this->assertSame(2, $this->repository->deleteAll());
        $this->assertSame(0, Post::count());
        $this->assertSame(2, Post::onlyTrashed()->count());
    }

    public function test_restore_brings_back_a_soft_deleted_record_by_id_or_instance(): void
    {
        $post = Post::create(['title' => 'A']);
        $post->delete();

        $this->assertTrue($this->repository->restore($post->id));
        $this->assertNull($post->fresh()->deleted_at);

        $post->delete();
        $this->assertTrue($this->repository->restore($post->fresh()));
        $this->assertNull($post->fresh()->deleted_at);
    }

    public function test_restore_all_restores_everything_or_only_given_ids(): void
    {
        $posts = collect(['A', 'B', 'C'])->map(fn (string $t) => Post::create(['title' => $t]));
        $this->repository->deleteAll();

        $this->assertSame(1, $this->repository->restoreAll([$posts[0]->id]));
        $this->assertSame(1, Post::count());

        $this->assertSame(2, $this->repository->restoreAll());
        $this->assertSame(3, Post::count());
    }

    public function test_force_delete_removes_the_row_permanently(): void
    {
        $post = Post::create(['title' => 'A']);
        $post->delete();

        $this->assertTrue($this->repository->forceDelete($post->id));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_trashed_returns_only_soft_deleted_records(): void
    {
        Post::create(['title' => 'Kept']);
        $deleted = Post::create(['title' => 'Gone']);
        $deleted->delete();

        $trashed = $this->repository->trashed();

        $this->assertCount(1, $trashed);
        $this->assertSame('Gone', $trashed->first()->title);
    }

    public function test_soft_delete_operations_fail_loudly_on_models_without_soft_deletes(): void
    {
        Tag::create(['name' => 'php']);
        $repository = new TagRepository;

        $this->assertFalse($repository->usesSoftDeletes());

        $this->expectException(SoftDeletesNotEnabledException::class);
        $this->expectExceptionMessage('must use the SoftDeletes trait');

        $repository->restoreAll();
    }

    public function test_service_exposes_soft_delete_operations_with_hooks(): void
    {
        $service = $this->app->make(PostService::class);
        $post = $service->create(['title' => 'hello']);

        $service->delete($post->id);
        $this->assertTrue($service->restore($post->id));
        $this->assertContains('restored:'.$post->id, $service->events);

        $service->deleteMany([$post->id]);
        $this->assertCount(1, $service->trashed());

        $this->assertSame(1, $service->restoreAll());
        $this->assertTrue($service->forceDelete($post->id));
        $this->assertContains('force-deleted:'.$post->id, $service->events);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
