<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * What will happen if the developer continues — produced by core, never by the UI.
 *
 * Phase 2.1: Change Understanding (ChangeSet + ArchitectureImpact). No Apply.
 */
final readonly class FixProposal
{
    public function __construct(
        public FixProposalId $id,
        public IssueId $issueId,
        public string $title,
        public string $description,
        public FixRisk $risk,
        public FixConfidence $confidence,
        public ChangeSet $changeSet,
        public VerificationPlan $verification,
        public FixProposalReasoning $reasoning,
        public FixProposalSummary $summary,
        public ArchitectureImpact $architectureImpact,
        public FixProposalStatus $status = FixProposalStatus::Created,
        public bool $applyEnabled = false,
    ) {}

    /** @return list<FileChange> */
    public function changes(): array
    {
        return $this->changeSet->files;
    }

    public function markViewed(): self
    {
        return $this->withStatus(FixProposalStatus::Viewed);
    }

    public function markReviewed(): self
    {
        return $this->withStatus(FixProposalStatus::Reviewed);
    }

    public function markAccepted(): self
    {
        return $this->withStatus(FixProposalStatus::Accepted);
    }

    public function markVerified(): self
    {
        return $this->withStatus(FixProposalStatus::Verified);
    }

    public function markCompleted(): self
    {
        return $this->withStatus(FixProposalStatus::Completed);
    }

    public function withStatus(FixProposalStatus $status): self
    {
        return new self(
            $this->id,
            $this->issueId,
            $this->title,
            $this->description,
            $this->risk,
            $this->confidence,
            $this->changeSet,
            $this->verification,
            $this->reasoning,
            $this->summary,
            $this->architectureImpact,
            $status,
            $this->applyEnabled,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'issue_id' => (string) $this->issueId,
            'title' => $this->title,
            'description' => $this->description,
            'summary' => $this->summary->toArray(),
            'risk' => $this->risk->value,
            'risk_label' => $this->risk->label(),
            'confidence' => $this->confidence->toArray(),
            'reasoning' => $this->reasoning->toArray(),
            'status' => $this->status->value,
            'change_set' => $this->changeSet->toArray(),
            // Alias for older consumers — prefer change_set.
            'changes' => $this->changeSet->toArray()['files'],
            'architecture_impact' => $this->architectureImpact->toArray(),
            'verification' => $this->verification->toArray(),
            'policy' => [
                'preview_required' => $this->risk->requiresPreview(),
                'apply_enabled' => $this->applyEnabled && $this->risk->allowsApply(),
                'apply_label' => ($this->applyEnabled && $this->risk->allowsApply())
                    ? 'Start Improvement'
                    : 'Apply Later',
            ],
        ];
    }
}
