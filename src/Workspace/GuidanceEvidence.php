<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Guidance-specific evidence — always visible with the recommendation.
 * Immutable counts from Memory (not interpretation).
 */
final readonly class GuidanceEvidence
{
    public function __construct(
        public int $similarImprovements,
        public int $resolvedIssues,
        public float $healthDeltaAverage,
        public int $contexts = 0,
        public int $remainingIssues = 0,
        public int $events = 0,
        public bool $recent = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'similar_improvements' => $this->similarImprovements,
            'resolved_issues' => $this->resolvedIssues,
            'health_delta_average' => $this->healthDeltaAverage,
            'contexts' => $this->contexts,
            'remaining_issues' => $this->remainingIssues,
            'events' => $this->events,
            'recent' => $this->recent,
        ];
    }
}
