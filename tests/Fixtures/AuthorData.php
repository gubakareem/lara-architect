<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Support\ArchitectData;

final class AuthorData extends ArchitectData
{
    public function __construct(
        public readonly string $fullName,
        public readonly ?string $email = null,
    ) {}
}
