<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use Illuminate\Http\Request;
use KarimAshraf\LaraArchitect\Tests\Concerns\CreatesPostsTable;
use KarimAshraf\LaraArchitect\Tests\Fixtures\Post;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostFilter;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostRepository;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class ArchitectQueryFilterTest extends TestCase
{
    use CreatesPostsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPostsTable();

        Post::create(['title' => 'Laravel tips', 'body' => 'Queues and jobs', 'published' => true]);
        Post::create(['title' => 'PHP generics', 'body' => 'Static analysis', 'published' => true]);
        Post::create(['title' => 'Laravel testing', 'body' => 'Pest and PHPUnit', 'published' => false]);
    }

    public function test_it_applies_matching_filter_methods_from_the_query_string(): void
    {
        $filter = new PostFilter(Request::create('/posts?search=Laravel&published=1'));

        $results = $filter->apply(Post::query())->get();

        $this->assertCount(1, $results);
        $this->assertSame('Laravel tips', $results->first()->title);
    }

    public function test_it_ignores_unknown_and_empty_parameters(): void
    {
        $filter = new PostFilter(Request::create('/posts?nonsense=1&search='));

        $this->assertCount(3, $filter->apply(Post::query())->get());
    }

    public function test_base_class_methods_cannot_be_invoked_as_filters(): void
    {
        // ?apply=x and ?filters=x must not call ArchitectQueryFilter internals.
        $filter = new PostFilter(Request::create('/posts?apply=x&filters=x'));

        $this->assertCount(3, $filter->apply(Post::query())->get());
    }

    public function test_snake_case_parameters_map_to_camel_case_methods(): void
    {
        $filter = new PostFilter(Request::create('/posts?published=0'));

        $results = $filter->apply(Post::query())->get();

        $this->assertCount(1, $results);
        $this->assertSame('Laravel testing', $results->first()->title);
    }

    public function test_repository_and_model_scope_integrate_with_filters(): void
    {
        $filter = new PostFilter(Request::create('/posts?published=1'));

        $page = (new PostRepository)->filter($filter, perPage: 10);
        $this->assertSame(2, $page->total());

        $filter = new PostFilter(Request::create('/posts?search=generics'));
        $this->assertCount(1, Post::filter($filter)->get());
    }
}
