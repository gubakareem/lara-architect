<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Database\ArchitectRepository;

/**
 * @extends ArchitectRepository<Post>
 */
class PostRepository extends ArchitectRepository
{
    protected function model(): string
    {
        return Post::class;
    }
}
