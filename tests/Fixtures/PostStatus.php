<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Enums\Concerns\EnumHelpers;

enum PostStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Published = 'published';
}
