<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture momentum — not a vanity score.
 * Positive when improvements outpace introduced drift.
 */
final readonly class ArchitectureMomentum
{
    public function __construct(
        public string $level,
        public string $reason,
        public int $improvementsCompleted,
        public int $driftIntroduced,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'momentum' => $this->level,
            'reason' => $this->reason,
            'evidence' => [
                'improvements_completed' => $this->improvementsCompleted,
                'drift_introduced' => $this->driftIntroduced,
            ],
        ];
    }
}
