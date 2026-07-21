<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Database\BaseRepository;

/**
 * @extends BaseRepository<Post>
 */
class PostRepository extends BaseRepository
{
    protected function model(): string
    {
        return Post::class;
    }
}
