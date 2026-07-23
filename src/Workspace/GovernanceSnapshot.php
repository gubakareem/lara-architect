<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Stable governance read contract — Workspace · CLI · future VS Code / GitHub / reports.
 * Not a dashboard model.
 */
final readonly class GovernanceSnapshot
{
    public function __construct(
        public string $standard,
        public int $alignmentScore,
        public string $direction,
        public int $completedImprovements,
        public int $remainingDrift,
        public string $confidence,
        public string $lastUpdated,
        public string $principle = '',
        public string $version = '1.0',
        public ?ArchitectureConcept $concept = null,
    ) {}

    public static function fromAlignment(StandardAlignment $alignment, string $confidence, string $lastUpdated): self
    {
        return new self(
            standard: $alignment->standard->concept->id,
            alignmentScore: $alignment->alignmentPercent,
            direction: $alignment->trend,
            completedImprovements: $alignment->improvementsCompleted,
            remainingDrift: $alignment->remainingDrift,
            confidence: $confidence,
            lastUpdated: $lastUpdated,
            principle: $alignment->standard->principle,
            version: $alignment->standard->version,
            concept: $alignment->standard->concept,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'standard' => $this->standard,
            'alignment' => [
                'score' => $this->alignmentScore,
                'direction' => $this->direction,
            ],
            'evidence' => [
                'completed_improvements' => $this->completedImprovements,
                'remaining_drift' => $this->remainingDrift,
            ],
            'confidence' => $this->confidence,
            'last_updated' => $this->lastUpdated,
            'principle' => $this->principle,
            'version' => $this->version,
            'concept' => $this->concept?->toArray(),
        ];
    }
}
