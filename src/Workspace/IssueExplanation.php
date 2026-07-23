<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Curated architecture education — AI may enhance later; it does not own this knowledge.
 */
final readonly class IssueExplanation
{
    /**
     * @param  list<string>  $benefits
     * @param  list<string>  $recommendedActions
     */
    public function __construct(
        public string $why,
        public ImprovementImpact $impact,
        public array $benefits,
        public string $recommendedFix,
        public array $recommendedActions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'why' => $this->why,
            'impact' => $this->impact->toArray(),
            'benefits' => $this->benefits,
            'recommended_fix' => $this->recommendedFix,
            'recommended_actions' => $this->recommendedActions,
        ];
    }
}
