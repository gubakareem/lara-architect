<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 15 — living architectural personality of this codebase.
 * Not a score. Not a grade. Discovered from behavior — with inertia.
 */
final readonly class ArchitectureIdentity
{
    /**
     * @param  list<string>  $principles  Soft aliases for UI
     * @param  list<string>  $strongAreas
     * @param  list<string>  $growingAreas
     * @param  list<string>  $evidence
     * @param  list<IdentityHistoryEntry>  $history
     */
    public function __construct(
        public string $question,
        public string $style,
        public array $principles,
        public array $strongAreas,
        public array $growingAreas,
        public string $summary,
        public ArchitectureIdentitySnapshot $snapshot,
        public array $evidence = [],
        public array $history = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'style' => $this->style,
            'principles' => $this->principles,
            'strong_areas' => $this->strongAreas,
            'growing_areas' => $this->growingAreas,
            'summary' => $this->summary,
            'evidence' => $this->evidence,
            'snapshot' => $this->snapshot->toArray(),
            'history' => array_map(
                static fn (IdentityHistoryEntry $e): array => $e->toArray(),
                $this->history,
            ),
            'kind' => 'identity',
        ];
    }
}
