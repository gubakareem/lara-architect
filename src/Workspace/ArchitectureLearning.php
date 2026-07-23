<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 10 — Architecture Learning read model.
 * Answers: “What has this system learned about itself?”
 * Reusable knowledge from history — not machine learning, not AI.
 */
final readonly class ArchitectureLearning
{
    /**
     * @param  list<SuccessfulEvolutionPattern>  $successfulPatterns
     * @param  list<EvolutionRisk>  $risks
     * @param  list<PreferredPath>  $preferredPaths
     * @param  list<ArchitectureChangeIntent>  $recentIntents
     */
    public function __construct(
        public string $question,
        public string $summary,
        public array $successfulPatterns,
        public array $risks,
        public array $preferredPaths,
        public array $recentIntents = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'summary' => $this->summary,
            'successful_evolution_patterns' => array_map(
                static fn (SuccessfulEvolutionPattern $p): array => $p->toArray(),
                $this->successfulPatterns,
            ),
            'evolution_risks' => array_map(
                static fn (EvolutionRisk $r): array => $r->toArray(),
                $this->risks,
            ),
            'preferred_paths' => array_map(
                static fn (PreferredPath $p): array => $p->toArray(),
                $this->preferredPaths,
            ),
            'recent_intents' => array_map(
                static fn (ArchitectureChangeIntent $i): array => $i->toArray(),
                $this->recentIntents,
            ),
        ];
    }
}
