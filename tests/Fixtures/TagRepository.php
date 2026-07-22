<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Database\ArchitectRepository;

/**
 * @extends ArchitectRepository<Tag>
 */
class TagRepository extends ArchitectRepository
{
    protected function model(): string
    {
        return Tag::class;
    }
}
