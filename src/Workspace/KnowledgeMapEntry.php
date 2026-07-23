<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One standard / concept connected to improvements and human knowledge.
 * Relationships only — not a graph UI.
 */
final readonly class KnowledgeMapEntry
{
    /**
     * @param  list<string>  $usedBy
     */
    public function __construct(
        public ArchitectureConcept $concept,
        public array $usedBy,
        public int $rationaleCount,
        public int $noteCount,
        public string $summary = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'concept' => $this->concept->toArray(),
            'standard' => $this->concept->label,
            'used_by' => $this->usedBy,
            'documented_by' => [
                'rationales' => $this->rationaleCount,
                'notes' => $this->noteCount,
            ],
            'summary' => $this->summary !== '' ? $this->summary : sprintf(
                '%s — used by %d improvement%s · documented by %d rationale%s · %d note%s',
                $this->concept->label,
                count($this->usedBy),
                count($this->usedBy) === 1 ? '' : 's',
                $this->rationaleCount,
                $this->rationaleCount === 1 ? '' : 's',
                $this->noteCount,
                $this->noteCount === 1 ? '' : 's',
            ),
        ];
    }
}
