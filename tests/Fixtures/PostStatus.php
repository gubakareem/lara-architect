<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
