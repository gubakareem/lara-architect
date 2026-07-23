<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Queryable architecture knowledge over a period — not a dashboard.
 */
final readonly class ArchitectureTrend
{
    public function __construct(
        public string $period,
        public int $improvements,
        public int $healthDelta,
        public int $resolvedIssues,
        public int $proposalsViewed = 0,
        public int $failedVerifications = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'improvements' => $this->improvements,
            'health_delta' => ($this->healthDelta >= 0 ? '+' : '').$this->healthDelta,
            'resolved_issues' => $this->resolvedIssues,
            'proposals_viewed' => $this->proposalsViewed,
            'failed_verifications' => $this->failedVerifications,
        ];
    }
}
