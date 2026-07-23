<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Where an Architecture Answer drew evidence from.
 * Makes every answer explainable — and prevents silent hallucination later.
 */
enum ArchitectureSourceType: string
{
    case Event = 'event';
    case Session = 'session';
    case Story = 'story';
    case Decision = 'decision';
    case Rationale = 'rationale';
    case Note = 'note';
    case Standard = 'standard';
    case Learning = 'learning';
    case Ownership = 'ownership';
    case Regression = 'regression';
    case Replay = 'replay';
    case History = 'history';
    case ContextBrief = 'context_brief';
    case KnowledgeTransfer = 'knowledge_transfer';
}
