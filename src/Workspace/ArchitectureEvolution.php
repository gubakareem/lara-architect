<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 9 — Architecture Evolution read model.
 * Standards → Governance → Evolution → (eventually) new Standards.
 * Not automation. Not AI. Direction from evidence.
 */
final readonly class ArchitectureEvolution
{
    /**
     * @param  list<ArchitectureTrajectory>  $trajectories
     * @param  list<ArchitectureRegression>  $regressions
     */
    public function __construct(
        public ?ArchitectureDirection $direction,
        public ArchitectureMomentum $momentum,
        public array $trajectories,
        public array $regressions,
        public string $summary,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'direction' => $this->direction?->toArray(),
            'momentum' => $this->momentum->toArray(),
            'trajectories' => array_map(
                static fn (ArchitectureTrajectory $t): array => $t->toArray(),
                $this->trajectories,
            ),
            'regressions' => array_map(
                static fn (ArchitectureRegression $r): array => $r->toArray(),
                $this->regressions,
            ),
            'summary' => $this->summary,
        ];
    }
}
