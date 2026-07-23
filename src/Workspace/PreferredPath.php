<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 10 — when this issue appears, history prefers this path.
 */
final readonly class PreferredPath
{
    public function __construct(
        public string $whenIssue,
        public ArchitectureConcept $preferredSolution,
        public int $timesChosen,
        public float $successRate,
        public LearningEvidence $evidence,
        public string $insteadOf = '',
        public string $summary = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'when_issue' => $this->whenIssue,
            'preferred_solution' => $this->preferredSolution->toArray(),
            'times_chosen' => $this->timesChosen,
            'success_rate' => $this->successRate,
            'instead_of' => $this->insteadOf,
            'evidence' => $this->evidence->toArray(),
            'summary' => $this->summary !== '' ? $this->summary : sprintf(
                'When “%s” appears, prefer %s (chosen %d×, %.0f%% success).',
                $this->whenIssue,
                $this->preferredSolution->label,
                $this->timesChosen,
                $this->successRate * 100,
            ),
        ];
    }
}
