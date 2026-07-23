<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 11.1 — relationships between Standards, Learning, and human knowledge.
 * Connects machine-observed facts with human-provided intent.
 */
final readonly class ArchitectureKnowledgeMap
{
    /**
     * @param  list<KnowledgeMapEntry>  $entries
     */
    public function __construct(
        public string $question,
        public string $summary,
        public array $entries,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'summary' => $this->summary,
            'entries' => array_map(
                static fn (KnowledgeMapEntry $entry): array => $entry->toArray(),
                $this->entries,
            ),
        ];
    }
}
