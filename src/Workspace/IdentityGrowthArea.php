<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Where the architecture is actively evolving (Growing — not "bad").
 */
final readonly class IdentityGrowthArea
{
    public function __construct(
        public string $area,
        public string $evidence,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'evidence' => $this->evidence,
        ];
    }
}
