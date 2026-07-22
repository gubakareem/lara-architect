<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Support\ArchitectData;

final class PostData extends ArchitectData
{
    public function __construct(
        public readonly string $title,
        public readonly bool $published = false,
        public readonly ?string $body = null,
        public readonly ?AuthorData $author = null,
        public readonly ?PostStatus $status = null,
    ) {}
}
