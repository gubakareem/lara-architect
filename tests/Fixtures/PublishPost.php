<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Actions\ArchitectAction;

class PublishPost extends ArchitectAction
{
    protected function handle(Post $post): Post
    {
        $post->update(['published' => true]);

        return $post->refresh();
    }
}
