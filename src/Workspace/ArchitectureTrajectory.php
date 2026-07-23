<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Period points along a valued standard — evidence of evolution over time.
 */
final readonly class ArchitectureTrajectory
{
    /**
     * @param  list<array{period: string, alignment: int, improvements: int}>  $points
     */
    public function __construct(
        public ArchitectureConcept $concept,
        public array $points,
        public string $summary,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'concept' => $this->concept->toArray(),
            'points' => $this->points,
            'summary' => $this->summary,
        ];
    }
}
