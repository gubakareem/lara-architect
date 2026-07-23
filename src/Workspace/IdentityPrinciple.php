<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One believed principle with evidence weight — not a slogan.
 */
final readonly class IdentityPrinciple
{
    public function __construct(
        public string $name,
        public int $evidenceCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'evidence_count' => $this->evidenceCount,
        ];
    }
}
