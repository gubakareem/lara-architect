<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Status of an option considered during a decision.
 */
enum AlternativeStatus: string
{
    case Considered = 'considered';
    case Rejected = 'rejected';
    case Deferred = 'deferred';
    case Chosen = 'chosen';
}
