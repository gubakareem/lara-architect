<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Product-layer timeline of architecture improvement events.
 * Not a UI feature yet — foundation for Replay · reports · GitHub · AI context.
 */
enum TimelineEventType: string
{
    case IssueFound = 'issue_found';
    case ProposalCreated = 'proposal_created';
    case ProposalViewed = 'proposal_viewed';
    case ProposalReviewed = 'proposal_reviewed';
    case ImprovementStarted = 'improvement_started';
    case FilesChanged = 'files_changed';
    case VerificationPassed = 'verification_passed';
    case VerificationFailed = 'verification_failed';
    case SessionCompleted = 'session_completed';
    case ConfidenceRecorded = 'confidence_recorded';
}
