<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Note ≠ Rationale — different knowledge types, different lifecycles.
 * Keep them separate; do not merge.
 */
enum KnowledgeLifecycle: string
{
    /** Short-lived context — e.g. "this module is currently being migrated." */
    case Contextual = 'contextual';

    /** Long-lived decision — e.g. "we chose this architecture because…" */
    case Permanent = 'permanent';
}
