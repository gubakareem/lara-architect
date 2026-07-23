<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 7/8 — Architecture Standard: Concept → Principle + versioned evidence.
 * Guiding (not a binary Rule). Versions remember what was valued when.
 */
final readonly class ArchitectureStandard
{
    public function __construct(
        public ArchitectureConcept $concept,
        public string $principle,
        public StandardEvidence $evidence,
        public string $summary,
        public string $version = '1.0',
    ) {}

    public function successfulImprovements(): int
    {
        return $this->evidence->successfulImprovements;
    }

    public function averageHealthImpact(): float
    {
        return $this->evidence->averageHealthDelta;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'concept' => $this->concept->toArray(),
            'principle' => $this->principle,
            'version' => $this->version,
            'evidence' => $this->evidence->toArray(),
            'summary' => $this->summary,
            // Soft aliases
            'successful_improvements' => $this->evidence->successfulImprovements,
            'average_health_impact' => $this->evidence->averageHealthDelta,
        ];
    }
}
