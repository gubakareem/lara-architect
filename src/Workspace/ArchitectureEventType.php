<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Memory source of truth — append-only facts.
 * Timeline / Replay / Insights are projections of this stream.
 */
enum ArchitectureEventType: string
{
    case IssueDetected = 'issue_detected';
    case ProposalCreated = 'proposal_created';
    case ProposalViewed = 'proposal_viewed';
    case ProposalReviewed = 'proposal_reviewed';
    case ImprovementStarted = 'improvement_started';
    case FilesChanged = 'files_changed';
    case VerificationPassed = 'verification_passed';
    case VerificationFailed = 'verification_failed';
    case SessionCompleted = 'session_completed';
    case ConfidenceRecorded = 'confidence_recorded';
    case BaselineCaptured = 'baseline_captured';
    case GuidanceViewed = 'guidance_viewed';
    case GuidanceAccepted = 'guidance_accepted';
    case GuidanceDismissed = 'guidance_dismissed';
    case ChangeIntentRecorded = 'change_intent_recorded';
    case NoteAdded = 'note_added';
    case RationaleRecorded = 'rationale_recorded';
    case OwnershipRecorded = 'ownership_recorded';
    case ConversationStarted = 'conversation_started';
    case ConversationEntryAdded = 'conversation_entry_added';
    case ConversationDecisionReached = 'conversation_decision_reached';
    case ConversationClosed = 'conversation_closed';
    case IdentityObserved = 'identity_observed';
}
