<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 11 — Architecture Collaboration read model (Workspace knowledge sharing).
 * Not GitHub. Not VS Code. Human explanations attached to architectural memory.
 */
final readonly class ArchitectureCollaboration
{
    /**
     * @param  list<ArchitectureNote>  $notes
     * @param  list<ArchitectureRationale>  $rationales
     */
    public function __construct(
        public string $question,
        public string $summary,
        public array $notes,
        public array $rationales,
        public ?ArchitectureOwnership $ownership = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'summary' => $this->summary,
            'notes' => array_map(
                static fn (ArchitectureNote $note): array => $note->toArray(),
                $this->notes,
            ),
            'rationales' => array_map(
                static fn (ArchitectureRationale $rationale): array => $rationale->toArray(),
                $this->rationales,
            ),
            'ownership' => $this->ownership?->toArray(),
            'knowledge_types' => [
                'note' => KnowledgeLifecycle::Contextual->value,
                'rationale' => KnowledgeLifecycle::Permanent->value,
            ],
        ];
    }
}
