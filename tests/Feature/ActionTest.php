<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use KarimAshraf\LaraArchitect\Tests\Concerns\CreatesPostsTable;
use KarimAshraf\LaraArchitect\Tests\Fixtures\Post;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PublishPost;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class ActionTest extends TestCase
{
    use CreatesPostsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPostsTable();
    }

    public function test_run_resolves_the_action_and_executes_it(): void
    {
        $post = Post::create(['title' => 'Draft']);

        $published = PublishPost::run($post);

        $this->assertTrue($published->published);
    }

    public function test_action_is_invokable(): void
    {
        $post = Post::create(['title' => 'Draft']);

        $published = (new PublishPost)($post);

        $this->assertTrue($published->published);
    }
}
