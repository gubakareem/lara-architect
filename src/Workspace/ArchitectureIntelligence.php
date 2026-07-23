<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 5 / 5.1 — typed Architecture Intelligence read model.
 * Domain insights age better than one GenericInsight bag.
 */
final readonly class ArchitectureIntelligence
{
    /**
     * @param  list<MostImprovedAreaInsight>  $mostImprovedAreas
     * @param  list<RepeatedProblemInsight>  $repeatedProblems
     * @param  list<ArchitectureDriftInsight>  $driftSignals
     * @param  list<ImprovementPatternInsight>  $commonPatterns
     */
    public function __construct(
        public array $mostImprovedAreas,
        public array $repeatedProblems,
        public array $driftSignals,
        public array $commonPatterns,
        public string $summary,
    ) {}

    /**
     * @return list<ExplainableInsight>
     */
    public function insights(): array
    {
        return [
            ...$this->mostImprovedAreas,
            ...$this->repeatedProblems,
            ...$this->driftSignals,
            ...$this->commonPatterns,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'most_improved_areas' => array_map(
                static fn (MostImprovedAreaInsight $i): array => $i->toArray(),
                $this->mostImprovedAreas,
            ),
            'repeated_problems' => array_map(
                static fn (RepeatedProblemInsight $i): array => $i->toArray(),
                $this->repeatedProblems,
            ),
            'architecture_drift' => array_map(
                static fn (ArchitectureDriftInsight $i): array => $i->toArray(),
                $this->driftSignals,
            ),
            'common_improvement_patterns' => array_map(
                static fn (ImprovementPatternInsight $i): array => $i->toArray(),
                $this->commonPatterns,
            ),
            'insights' => array_map(
                static fn (ExplainableInsight $i): array => $i->toArray(),
                $this->insights(),
            ),
        ];
    }
}
