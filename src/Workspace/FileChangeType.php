<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

enum FileChangeType: string
{
    case Created = 'created';
    case Modified = 'modified';
    case Deleted = 'deleted';
}
