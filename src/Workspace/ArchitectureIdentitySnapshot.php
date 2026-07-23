<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Stable Identity contract — Workspace · CLI · VS Code · GitHub · AI later.
 * No grade. No ranking. Just identity with inertia-aware confidence.
 */
final readonly class ArchitectureIdentitySnapshot
{
    /**
     * @param  list<IdentityPrinciple>  $principles
     * @param  list<IdentityStrength>  $strengths
     * @param  list<IdentityGrowthArea>  $growthAreas
     */
    public function __construct(
        public string $styleName,
        public string $styleConfidence,
        public array $principles,
        public array $strengths,
        public array $growthAreas,
        public string $updatedAt,
        public string $summary = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'style' => [
                'name' => $this->styleName,
                'confidence' => $this->styleConfidence,
            ],
            'principles' => array_map(
                static fn (IdentityPrinciple $p): array => $p->toArray(),
                $this->principles,
            ),
            'strengths' => array_map(
                static fn (IdentityStrength $s): array => $s->toArray(),
                $this->strengths,
            ),
            'growth_areas' => array_map(
                static fn (IdentityGrowthArea $g): array => $g->toArray(),
                $this->growthAreas,
            ),
            'updated_at' => $this->updatedAt,
            'summary' => $this->summary,
            'kind' => 'identity_snapshot',
        ];
    }
}
