<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Future analytics event — why a developer left a proposal without continuing.
 * Not a UI feature yet; reserved for Proposal Trust / Abandonment metrics.
 */
enum ProposalDismissReason: string
{
    case TooRisky = 'too_risky';
    case Unclear = 'unclear';
    case NotNow = 'not_now';
    case WrongContext = 'wrong_context';
}
