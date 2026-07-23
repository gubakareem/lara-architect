<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

enum WorkspaceContextType: string
{
    case Project = 'project';
    case Module = 'module';
    case File = 'file';
    case Violation = 'violation';
    case Fix = 'fix';
}
