<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * What a human note/rationale is attached to.
 */
enum CollaborationSubject: string
{
    case Standard = 'standard';
    case Decision = 'decision';
    case Improvement = 'improvement';
    case Regression = 'regression';
    case Concept = 'concept';
    case Context = 'context';
}
