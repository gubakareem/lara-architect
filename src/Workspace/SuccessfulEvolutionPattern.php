<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 10 — what this codebase has learned about itself (not ML).
 */
final readonly class SuccessfulEvolutionPattern
{
    public function __construct(
        public ArchitectureConcept $concept,
        public int $applied,
        public float $successRate,
        public float $averageHealthImpact,
        public string $summary,
        public LearningEvidence $evidence,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pattern' => $this->concept->id,
            'concept' => $this->concept->toArray(),
            'applied' => $this->applied,
            'success_rate' => $this->successRate,
            'average_health_impact' => $this->averageHealthImpact,
            'summary' => $this->summary,
            'evidence' => $this->evidence->toArray(),
        ];
    }
}
