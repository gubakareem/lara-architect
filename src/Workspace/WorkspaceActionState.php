<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Lifecycle of a Workspace action (UI: show Preview when previewable, Apply when executable).
 */
enum WorkspaceActionState: string
{
    case Available = 'available';
    case Previewable = 'previewable';
    case Executable = 'executable';
    case Completed = 'completed';
    case Failed = 'failed';
}
