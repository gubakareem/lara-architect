<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 10 — evolution risk learned from regressions / recurrence.
 */
final readonly class EvolutionRisk
{
    /**
     * @param  list<string>  $evidenceLines
     */
    public function __construct(
        public string $risk,
        public int $previousRegressions,
        public array $evidenceLines,
        public ?ArchitectureConcept $relatedConcept = null,
        public ?LearningEvidence $learningEvidence = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'risk' => $this->risk,
            'previous_regressions' => $this->previousRegressions,
            'evidence' => $this->evidenceLines,
            'learning_evidence' => $this->learningEvidence?->toArray(),
            'related_concept' => $this->relatedConcept?->toArray(),
        ];
    }
}
