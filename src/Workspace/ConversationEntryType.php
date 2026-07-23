<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Conversation entry kinds — reasoning steps, not chat messages.
 */
enum ConversationEntryType: string
{
    case Question = 'question';
    case Evidence = 'evidence';
    case Opinion = 'opinion';
    case Decision = 'decision';
    case Rationale = 'rationale';
}
