<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 12 — living onboarding for an area / module.
 * Not documentation generation — teaches history from Memory + Collaboration.
 */
final readonly class ArchitectureOnboarding
{
    /**
     * @param  list<string>  $importantDecisions
     * @param  list<string>  $recentEvolution
     * @param  list<string>  $knownRisks
     */
    public function __construct(
        public string $area,
        public string $welcome,
        public string $currentDirection,
        public array $importantDecisions,
        public array $recentEvolution,
        public array $knownRisks,
        public ?ArchitectureOwnership $ownership = null,
        public string $summary = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'welcome' => $this->welcome,
            'current_direction' => $this->currentDirection,
            'important_decisions' => $this->importantDecisions,
            'recent_evolution' => $this->recentEvolution,
            'known_risks' => $this->knownRisks,
            'ownership' => $this->ownership?->toArray(),
            'summary' => $this->summary,
        ];
    }
}
