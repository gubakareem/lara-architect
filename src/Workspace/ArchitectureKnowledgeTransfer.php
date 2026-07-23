<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 12 — Architecture Knowledge Transfer read model.
 * Goal: a new developer understands the codebase faster from living history.
 * AI should consume this knowledge later — not invent it.
 */
final readonly class ArchitectureKnowledgeTransfer
{
    public function __construct(
        public string $question,
        public string $summary,
        public ?ArchitectureOnboarding $onboarding,
        public ?ContextBrief $brief,
        public ?ArchitectureKnowledgeMap $knowledgeMap,
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
            'onboarding' => $this->onboarding?->toArray(),
            'context_brief' => $this->brief?->toArray(),
            'knowledge_map' => $this->knowledgeMap?->toArray(),
            'ownership' => $this->ownership?->toArray(),
        ];
    }
}
