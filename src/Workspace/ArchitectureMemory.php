<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Records architecture facts into the Event Stream with correlation chains.
 * Controlled Change / Preview / Analysis call this — UI never invents history.
 */
final class ArchitectureMemory
{
    public function __construct(
        private readonly ArchitectureEventStore $store = new ArchitectureEventStore,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $projectRoot,
        ArchitectureEventType $type,
        string $context,
        array $payload = [],
        ?EventCorrelation $correlation = null,
    ): ArchitectureEvent {
        $event = ArchitectureEvent::make($type, $context, $payload, $correlation);
        $this->store->append($projectRoot, $event);

        return $event;
    }

    public function recordIssueDetected(string $projectRoot, WorkspaceIssue $issue, string $context): EventCorrelation
    {
        $findingId = isset($issue->findings[0]) ? (string) $issue->findings[0]->id : null;
        $correlation = EventCorrelation::empty()->with(
            findingId: $findingId,
            issueId: (string) $issue->id,
        );

        $this->record($projectRoot, ArchitectureEventType::IssueDetected, $context, [
            'issue_id' => (string) $issue->id,
            'title' => $issue->title,
            'path' => $issue->path,
            'finding_id' => $correlation->findingId,
        ], $correlation);

        return $correlation;
    }

    public function recordProposalCreated(
        string $projectRoot,
        FixProposal $proposal,
        string $context,
        ?EventCorrelation $correlation = null,
    ): EventCorrelation {
        $correlation = ($correlation ?? EventCorrelation::empty())->with(
            proposalId: (string) $proposal->id,
            issueId: (string) $proposal->issueId,
        );

        $this->record($projectRoot, ArchitectureEventType::ProposalCreated, $context, [
            'proposal_id' => (string) $proposal->id,
            'issue_id' => (string) $proposal->issueId,
            'title' => $proposal->title,
            'risk' => $proposal->risk->value,
        ], $correlation);

        return $correlation;
    }

    public function recordProposalViewed(
        string $projectRoot,
        FixProposal $proposal,
        string $context,
        ?EventCorrelation $correlation = null,
    ): EventCorrelation {
        $correlation = ($correlation ?? EventCorrelation::empty())->with(
            proposalId: (string) $proposal->id,
            issueId: (string) $proposal->issueId,
        );

        $this->record($projectRoot, ArchitectureEventType::ProposalViewed, $context, [
            'proposal_id' => (string) $proposal->id,
            'issue_id' => (string) $proposal->issueId,
        ], $correlation);

        return $correlation;
    }

    public function recordFromExecution(
        string $projectRoot,
        string $context,
        ProposalReviewed $reviewed,
        ChangeExecution $execution,
        ?ArchitectureSession $session,
        ?EventCorrelation $correlation = null,
    ): void {
        $correlation = ($correlation ?? EventCorrelation::empty())->with(
            proposalId: (string) $execution->proposalId,
            executionId: (string) $execution->id,
            sessionId: $session !== null ? (string) $session->id : null,
        );

        $reviewedPayload = $reviewed->toArray();
        $correlation = $correlation->with(
            proposalId: (string) ($reviewedPayload['proposal_id'] ?? $correlation->proposalId),
        );

        $this->record($projectRoot, ArchitectureEventType::ProposalReviewed, $context, $reviewedPayload, $correlation);
        $this->record($projectRoot, ArchitectureEventType::ImprovementStarted, $context, [
            'execution_id' => (string) $execution->id,
            'proposal_id' => (string) $execution->proposalId,
        ], $correlation);

        foreach ($execution->events as $event) {
            $type = match ($event->type) {
                ExecutionEventType::FilesChanged => ArchitectureEventType::FilesChanged,
                ExecutionEventType::VerificationPassed => ArchitectureEventType::VerificationPassed,
                ExecutionEventType::VerificationFailed => ArchitectureEventType::VerificationFailed,
                ExecutionEventType::SessionCompleted => ArchitectureEventType::SessionCompleted,
                default => null,
            };

            if ($type === null) {
                continue;
            }

            $payload = $event->payload;
            if ($type === ArchitectureEventType::SessionCompleted && $session !== null) {
                $payload['session_id'] = (string) $session->id;
                $payload['goal'] = $session->goal;
                $payload['health_before'] = $session->healthBefore;
                $payload['health_after'] = $session->healthAfter;
                $payload['changes'] = $session->changes;
                $payload['proposal_id'] = (string) $session->proposalId;
                $payload['execution_id'] = (string) $session->executionId;
                $correlation = $correlation->with(sessionId: (string) $session->id);
            }

            $this->record($projectRoot, $type, $context, $payload, $correlation);
        }
    }

    /**
     * @return list<ArchitectureEvent>
     */
    public function eventsForContext(string $projectRoot, string $context, ?int $limit = null): array
    {
        return $this->store->forContext($projectRoot, $context, $limit);
    }

    /**
     * @return list<ArchitectureEvent>
     */
    public function allEvents(string $projectRoot, ?int $limit = null): array
    {
        return $this->store->all($projectRoot, $limit);
    }

    /**
     * Guidance Decision Memory — which recommendations are useful / ignored.
     *
     * @param  array<string, mixed>  $extra
     */
    public function recordGuidanceDecision(
        string $projectRoot,
        string $context,
        GuidanceDecision $decision,
        ArchitectureConcept $concept,
        ?GuidanceDismissReason $reason = null,
        array $extra = [],
        ?EventCorrelation $correlation = null,
    ): ArchitectureEvent {
        $type = match ($decision) {
            GuidanceDecision::Viewed => ArchitectureEventType::GuidanceViewed,
            GuidanceDecision::Accepted => ArchitectureEventType::GuidanceAccepted,
            GuidanceDecision::Dismissed => ArchitectureEventType::GuidanceDismissed,
        };

        $payload = array_merge([
            'concept' => $concept->label,
            'concept_id' => $concept->id,
            'decision' => $decision->value,
            'reason' => $reason?->value,
        ], $extra);

        return $this->record($projectRoot, $type, $context, $payload, $correlation);
    }

    /**
     * Record intentional evolution — distinguishes planned change from accidental drift.
     */
    public function recordChangeIntent(
        string $projectRoot,
        ArchitectureChangeIntent $intent,
        string $context,
        ?EventCorrelation $correlation = null,
    ): ArchitectureEvent {
        return $this->record(
            $projectRoot,
            ArchitectureEventType::ChangeIntentRecorded,
            $context !== '' ? $context : $intent->area,
            $intent->toArray(),
            $correlation,
        );
    }
}
