<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Database\BaseRepository;

/**
 * @extends BaseRepository<Tag>
 */
class TagRepository extends BaseRepository
{
    protected function model(): string
    {
        return Tag::class;
    }
}
