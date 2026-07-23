<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * What a conversation is attached to — not a global chat.
 */
enum ConversationSubject: string
{
    case Standard = 'standard';
    case Improvement = 'improvement';
    case Regression = 'regression';
    case Rationale = 'rationale';
    case Decision = 'decision';
    case Context = 'context';
    case Concept = 'concept';
}
