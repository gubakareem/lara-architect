<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 6.1 — Guided Improvement Journey (read model).
 * Why now? groups Current → History → Direction.
 * Opportunity stays separate from Action.
 */
final readonly class GuidedImprovementJourney
{
    /**
     * @param  list<string>  $currentState
     * @param  list<string>  $history
     * @param  list<string>  $expectedDirection
     * @param  list<array<string, mixed>>  $relatedHistory
     */
    public function __construct(
        public ArchitectureGuidance $guidance,
        public array $currentState,
        public array $history,
        public array $expectedDirection,
        public array $relatedHistory,
        public bool $canCreateProposal,
        public ?string $proposeIssueId = null,
        public string $nextStep = 'explore',
        public ?ArchitectureStandard $standard = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'guidance' => $this->guidance->toArray(),
            'why_now' => [
                'current_state' => $this->currentState,
                'history' => $this->history,
                'expected_direction' => $this->expectedDirection,
                // Soft aliases for older consumers
                'historical_evidence' => $this->history,
                'expected_impact' => $this->expectedDirection[0] ?? '',
            ],
            'related_history' => $this->relatedHistory,
            'related_evidence' => [
                'current' => $this->currentState,
                'history' => $this->history,
                'direction' => $this->expectedDirection,
            ],
            'opportunity' => $this->guidance->opportunity,
            'standard' => $this->standard?->toArray(),
            'action' => [
                'available' => $this->canCreateProposal,
                'label' => $this->canCreateProposal
                    ? 'Create Improvement Proposal'
                    : 'Select a related issue to propose',
                'issue_id' => $this->proposeIssueId,
                'kind' => 'create_proposal',
            ],
            'next_step' => $this->nextStep,
            'flow' => [
                'Guidance',
                'Explore',
                'Related history',
                'Create proposal',
                'Controlled Change',
            ],
        ];
    }
}
