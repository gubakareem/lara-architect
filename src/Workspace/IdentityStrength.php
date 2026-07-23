<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Where the architecture consistently succeeds (Strong — not "good").
 */
final readonly class IdentityStrength
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
