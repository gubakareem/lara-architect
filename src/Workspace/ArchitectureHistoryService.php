<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Projects Architecture Events → History / Replay.
 * Events remain source of truth; this is a read model.
 */
final class ArchitectureHistoryService
{
    private readonly ArchitectureDecisionMemory $decisions;

    private readonly ArchitectureIntelligenceService $intelligence;

    private readonly ArchitectureGuidanceService $guidance;

    private readonly GuidedImprovementJourneyService $journey;

    private readonly ArchitectureGovernanceService $governance;

    private readonly ArchitectureEvolutionService $evolution;

    private readonly ArchitectureLearningService $learning;

    private readonly ArchitectureCollaborationService $collaboration;

    private readonly ArchitectureKnowledgeTransferService $knowledgeTransfer;

    private readonly ArchitectureConversationService $conversations;

    private readonly ArchitectureDecisionHistoryService $decisionHistory;

    private readonly ArchitectureIdentityService $identity;

    private readonly ArchitectureCommunicationService $communication;

    private readonly ArchitectureContextService $architectureContext;

    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureBaselineStore $baselines = new ArchitectureBaselineStore,
        private readonly ArchitectureStoryProjector $stories = new ArchitectureStoryProjector,
        ?ArchitectureDecisionMemory $decisions = null,
        ?ArchitectureIntelligenceService $intelligence = null,
        ?ArchitectureGuidanceService $guidance = null,
        ?GuidedImprovementJourneyService $journey = null,
        ?ArchitectureGovernanceService $governance = null,
        ?ArchitectureEvolutionService $evolution = null,
        ?ArchitectureLearningService $learning = null,
        ?ArchitectureCollaborationService $collaboration = null,
        ?ArchitectureKnowledgeTransferService $knowledgeTransfer = null,
        ?ArchitectureConversationService $conversations = null,
        ?ArchitectureDecisionHistoryService $decisionHistory = null,
        ?ArchitectureIdentityService $identity = null,
        ?ArchitectureCommunicationService $communication = null,
        ?ArchitectureContextService $architectureContext = null,
    ) {
        $this->decisions = $decisions ?? new ArchitectureDecisionMemory($this->memory, $this->stories);
        $this->intelligence = $intelligence ?? new ArchitectureIntelligenceService($this->memory, $this->baselines);
        $this->guidance = $guidance ?? new ArchitectureGuidanceService($this->intelligence, new ArchitectureVocabulary, $this->baselines);
        $this->journey = $journey ?? new GuidedImprovementJourneyService(
            $this->guidance,
            $this->memory,
            $this->stories,
            $this->intelligence,
        );
        $standards = new ArchitectureStandardsService(new ArchitectureVocabulary, $this->intelligence, $this->memory);
        $this->governance = $governance ?? new ArchitectureGovernanceService($standards, $this->intelligence);
        $this->evolution = $evolution ?? new ArchitectureEvolutionService(
            $this->memory,
            new ArchitectureVocabulary,
            $this->intelligence,
            $this->governance,
        );
        $this->learning = $learning ?? new ArchitectureLearningService(
            $this->memory,
            new ArchitectureVocabulary,
            $this->intelligence,
            $this->evolution,
        );
        $this->collaboration = $collaboration ?? new ArchitectureCollaborationService($this->memory);
        $this->knowledgeTransfer = $knowledgeTransfer ?? new ArchitectureKnowledgeTransferService(
            $this->memory,
            $this->collaboration,
            $this->learning,
            $this->evolution,
            $this->decisions,
        );
        $this->conversations = $conversations ?? new ArchitectureConversationService($this->memory, $this->collaboration);
        $this->decisionHistory = $decisionHistory ?? new ArchitectureDecisionHistoryService(
            $this->conversations,
            $this->memory,
            $this->collaboration,
        );
        $this->identity = $identity ?? new ArchitectureIdentityService(
            $standards,
            $this->evolution,
            $this->learning,
            $this->governance,
            $this->decisionHistory,
            $this->intelligence,
            $this->memory,
        );
        $this->communication = $communication ?? new ArchitectureCommunicationService(
            $this->identity,
            $this->decisionHistory,
            $this->knowledgeTransfer,
            $this->evolution,
        );
        $this->architectureContext = $architectureContext ?? new ArchitectureContextService(
            $this->knowledgeTransfer,
            $this->identity,
            $this->decisionHistory,
            $this->evolution,
            $this->guidance,
            $this->learning,
            $this->communication,
        );
    }

    public function forContext(
        string $projectRoot,
        string $context,
        ?int $currentHealth = null,
        int $limit = 80,
    ): ArchitectureHistory {
        $events = $this->memory->eventsForContext($projectRoot, $context, $limit);
        $replay = [];
        $improvements = [];
        $latestSessionPayload = null;

        foreach ($events as $event) {
            $replay[] = $this->toReplayEntry($event);

            if ($event->type === ArchitectureEventType::SessionCompleted) {
                $improvements[] = new HistoryImprovement(
                    title: (string) ($event->payload['goal'] ?? 'Architecture improvement'),
                    occurredAt: $event->occurredAt,
                    sessionId: isset($event->payload['session_id']) ? (string) $event->payload['session_id'] : null,
                    healthBefore: isset($event->payload['health_before']) ? (int) $event->payload['health_before'] : null,
                    healthAfter: isset($event->payload['health_after']) ? (int) $event->payload['health_after'] : null,
                );
                $latestSessionPayload = $event->payload;
            }
        }

        $improvements = array_reverse($improvements);
        $improvements = array_slice($improvements, 0, 10);

        $confidence = null;
        if (is_array($latestSessionPayload)
            && isset($latestSessionPayload['health_before'], $latestSessionPayload['health_after'])) {
            $confidence = SessionConfidence::derive(
                $this->syntheticSession($context, $latestSessionPayload),
                isset($latestSessionPayload['developer_accepted'])
                    ? (bool) $latestSessionPayload['developer_accepted']
                    : null,
            );
        }

        foreach (array_reverse($events) as $event) {
            if ($event->type === ArchitectureEventType::ConfidenceRecorded) {
                $helped = (bool) ($event->payload['helped'] ?? false);
                if ($latestSessionPayload !== null) {
                    $confidence = SessionConfidence::derive(
                        $this->syntheticSession($context, $latestSessionPayload),
                        $helped,
                    );
                }

                break;
            }
        }

        $storyList = $this->stories->stories($events, $context);
        $trend = $this->stories->trend($events);

        $decisionList = $this->decisions->forContext($projectRoot, $context);
        $fileNeedle = str_ends_with(strtolower($context), '.php') ? $context : $context.'.php';
        $fileDecisions = $this->decisions->forFile($projectRoot, $fileNeedle, $context);
        $mergedDecisions = $this->uniqueDecisions([...$fileDecisions, ...$decisionList]);
        $intelligence = $this->intelligence->analyze($projectRoot);

        return new ArchitectureHistory(
            context: $context,
            currentHealth: $currentHealth,
            recentImprovements: $improvements,
            replay: $replay,
            baseline: $this->baselines->latest($projectRoot),
            latestConfidence: $confidence,
            stories: array_slice($storyList, 0, 5),
            trend: $trend,
            latestStory: $storyList[0] ?? null,
            decisions: array_slice($mergedDecisions, 0, 5),
            intelligence: $intelligence,
            guidance: $this->guidance->recommend(
                projectRoot: $projectRoot,
                context: $context,
                currentHealth: $currentHealth,
            ),
            journey: $this->journey->forContext(
                projectRoot: $projectRoot,
                context: $context,
                currentHealth: $currentHealth,
            ),
            governance: $this->governance->assess($projectRoot),
            evolution: $this->evolution->evolve($projectRoot),
            learning: $this->learning->learn($projectRoot),
            collaboration: $this->collaboration->forContext($projectRoot, $context),
            knowledgeTransfer: $this->knowledgeTransfer->transfer($projectRoot, $context),
            conversations: $this->conversations->forContext($projectRoot, $context),
            decisionHistory: $this->decisionHistory->forArea($projectRoot, $context),
            identity: $this->identity->identify($projectRoot),
            communication: $this->communication->communicate($projectRoot, $context),
            architectureContext: $this->architectureContext->forSubject($projectRoot, $context, $currentHealth),
        );
    }

    /**
     * @param  list<ArchitectureDecision>  $decisions
     * @return list<ArchitectureDecision>
     */
    private function uniqueDecisions(array $decisions): array
    {
        $seen = [];
        $unique = [];
        foreach ($decisions as $decision) {
            $key = $decision->correlation->chainKey().'|'.$decision->decision;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $decision;
        }

        return $unique;
    }

    private function toReplayEntry(ArchitectureEvent $event): ReplayEntry
    {
        $entry = match ($event->type) {
            ArchitectureEventType::IssueDetected => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                '⚠ '.(string) ($event->payload['title'] ?? 'Issue detected'),
                'warning',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ProposalCreated => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                '✓ Proposal created — '.(string) ($event->payload['title'] ?? 'Fix proposal'),
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ProposalViewed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Proposal viewed',
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ProposalReviewed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Proposal reviewed',
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ImprovementStarted => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Improvement started',
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::FilesChanged => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Files changed',
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::VerificationPassed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                '✓ Verification passed',
                'success',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::VerificationFailed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Verification failed',
                'danger',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::SessionCompleted => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                '✓ Improvement completed',
                'success',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ConfidenceRecorded => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                ((bool) ($event->payload['helped'] ?? false) ? 'Developer: helped' : 'Developer: not confirmed'),
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::BaselineCaptured => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Architecture baseline captured',
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::GuidanceViewed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Guidance viewed — '.(string) ($event->payload['concept'] ?? 'opportunity'),
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::GuidanceAccepted => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Guidance accepted — '.(string) ($event->payload['concept'] ?? 'opportunity'),
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::GuidanceDismissed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Guidance dismissed ('.(string) ($event->payload['reason'] ?? 'not_now').')',
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ChangeIntentRecorded => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Change intent — '.(string) ($event->payload['intent'] ?? 'intentional evolution'),
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::NoteAdded => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Architecture note — '.(string) ($event->payload['subject_key'] ?? 'shared knowledge'),
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::RationaleRecorded => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Architecture rationale recorded',
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::OwnershipRecorded => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Architecture ownership — '.(string) ($event->payload['area'] ?? 'area'),
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ConversationStarted => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Conversation started — '.(string) ($event->payload['topic'] ?? 'architecture'),
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ConversationEntryAdded => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Conversation '.(string) ($event->payload['type'] ?? 'entry').' — reasoning step',
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ConversationDecisionReached => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Conversation decision — '.(string) ($event->payload['decision'] ?? 'outcome'),
                'info',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::ConversationClosed => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Conversation closed',
                'muted',
                $event->payload,
                $event->correlation,
            ),
            ArchitectureEventType::IdentityObserved => new ReplayEntry(
                $event->occurredAt,
                $event->type->value,
                'Architecture identity — '.(string) ($event->payload['style'] ?? 'observed'),
                'info',
                $event->payload,
                $event->correlation,
            ),
        };

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syntheticSession(string $context, array $payload): ArchitectureSession
    {
        $before = (int) ($payload['health_before'] ?? 0);
        $after = (int) ($payload['health_after'] ?? $before);
        $goal = (string) ($payload['goal'] ?? 'Architecture improvement');
        $changes = isset($payload['changes']) && is_array($payload['changes'])
            ? array_values(array_map('strval', $payload['changes']))
            : [$goal];

        return new ArchitectureSession(
            id: SessionId::of((string) ($payload['session_id'] ?? 'session_unknown')),
            proposalId: FixProposalId::of((string) ($payload['proposal_id'] ?? 'fix:unknown')),
            executionId: ChangeExecutionId::of((string) ($payload['execution_id'] ?? 'exec:unknown')),
            context: $context,
            goal: $goal,
            healthBefore: $before,
            healthAfter: $after,
            changes: $changes,
            verificationSummary: ['pint' => 'passed', 'phpstan' => 'passed', 'tests' => 'passed'],
            verification: VerificationPlan::defaultPlan(),
            timeline: ArchitectureTimeline::empty(),
            completedAt: (string) ($payload['completed_at'] ?? gmdate('c')),
        );
    }
}
