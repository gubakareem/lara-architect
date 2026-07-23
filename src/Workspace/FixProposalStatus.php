<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Proposal lifecycle for Controlled Change + Replay.
 *
 * Opening Preview = viewed (not reviewed).
 * Explicit continue = reviewed → accepted → … → completed.
 */
enum FixProposalStatus: string
{
    case Created = 'created';
    case Viewed = 'viewed';
    case Reviewed = 'reviewed';
    case Accepted = 'accepted';
    case Verified = 'verified';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
