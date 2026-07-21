<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Support\BaseData;

final class AuthorData extends BaseData
{
    public function __construct(
        public readonly string $fullName,
        public readonly ?string $email = null,
    ) {}
}
