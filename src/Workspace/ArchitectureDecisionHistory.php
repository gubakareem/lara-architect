<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Projection of decisions only — powerful onboarding surface.
 * Not all conversations; the durable decision trail.
 */
final readonly class ArchitectureDecisionHistory
{
    /**
     * @param  list<ArchitectureDecisionRecord>  $decisions
     */
    public function __construct(
        public string $question,
        public string $summary,
        public array $decisions,
        public string $area = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'summary' => $this->summary,
            'area' => $this->area,
            'decisions' => array_map(
                static fn (ArchitectureDecisionRecord $d): array => $d->toArray(),
                $this->decisions,
            ),
        ];
    }
}
