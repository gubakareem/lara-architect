<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use KarimAshraf\LaraArchitect\Tests\Concerns\CreatesPostsTable;
use KarimAshraf\LaraArchitect\Tests\Fixtures\Post;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostRepository;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use CreatesPostsTable;

    private PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPostsTable();
        $this->repository = new PostRepository;
    }

    public function test_it_creates_and_finds_records(): void
    {
        $post = $this->repository->create(['title' => 'First']);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertNotEmpty($post->uuid, 'HasUuid should fill the uuid column');

        $found = $this->repository->find($post->id);
        $this->assertSame('First', $found->title);

        $byField = $this->repository->findBy('title', 'First');
        $this->assertTrue($found->is($byField));
    }

    public function test_it_updates_by_id_or_instance(): void
    {
        $post = $this->repository->create(['title' => 'Old']);

        $updated = $this->repository->update($post->id, ['title' => 'New']);
        $this->assertSame('New', $updated->title);

        $updated = $this->repository->update($post->fresh(), ['title' => 'Newer']);
        $this->assertSame('Newer', $updated->title);
    }

    public function test_it_deletes_and_paginates(): void
    {
        $first = $this->repository->create(['title' => 'One']);
        $this->repository->create(['title' => 'Two']);
        $this->repository->create(['title' => 'Three']);

        $this->assertTrue($this->repository->delete($first->id));
        $this->assertSoftDeleted('posts', ['id' => $first->id]);

        $page = $this->repository->paginate(perPage: 1);
        $this->assertSame(2, $page->total());
    }

    public function test_scoped_runs_ad_hoc_queries(): void
    {
        $this->repository->create(['title' => 'Published', 'published' => true]);
        $this->repository->create(['title' => 'Draft', 'published' => false]);

        $published = $this->repository->scoped(
            fn ($query) => $query->where('published', true)->get(),
        );

        $this->assertCount(1, $published);
        $this->assertSame('Published', $published->first()->title);
    }
}
