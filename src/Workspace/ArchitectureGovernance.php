<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 8 — Architecture Governance (developer feedback, not enterprise).
 * Answers: “Are we moving toward the architecture we value?”
 * Exposes GovernanceSnapshot as the stable read contract.
 */
final readonly class ArchitectureGovernance
{
    /**
     * @param  list<StandardAlignment>  $alignments
     * @param  list<GovernanceSnapshot>  $snapshots
     */
    public function __construct(
        public string $question,
        public string $summary,
        public int $overallAlignment,
        public string $overallTrend,
        public array $alignments,
        public array $snapshots = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'summary' => $this->summary,
            'overall_alignment' => $this->overallAlignment,
            'overall_trend' => $this->overallTrend,
            'alignments' => array_map(
                static fn (StandardAlignment $alignment): array => $alignment->toArray(),
                $this->alignments,
            ),
            'snapshots' => array_map(
                static fn (GovernanceSnapshot $snapshot): array => $snapshot->toArray(),
                $this->snapshots,
            ),
        ];
    }
}
