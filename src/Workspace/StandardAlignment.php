<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Alignment of one valued Standard with current Memory (developer governance).
 */
final readonly class StandardAlignment
{
    public function __construct(
        public ArchitectureStandard $standard,
        public int $alignmentPercent,
        public string $trend,
        public int $improvementsCompleted,
        public int $remainingDrift,
        public string $summary,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'standard' => $this->standard->concept->id,
            'concept' => $this->standard->concept->toArray(),
            'principle' => $this->standard->principle,
            'version' => $this->standard->version,
            'alignment' => $this->alignmentPercent,
            'trend' => $this->trend,
            'evidence' => [
                'improvements_completed' => $this->improvementsCompleted,
                'remaining_drift' => $this->remainingDrift,
                'standard_evidence' => $this->standard->evidence->toArray(),
            ],
            'summary' => $this->summary,
        ];
    }
}
