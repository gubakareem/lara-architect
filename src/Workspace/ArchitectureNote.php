<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 11 — human-added explanation attached to architecture knowledge.
 * Answers: "What should another developer know?"
 * Lifecycle: contextual (short-lived) — keep distinct from ArchitectureRationale.
 */
final readonly class ArchitectureNote
{
    public function __construct(
        public string $id,
        public CollaborationSubject $subjectType,
        public string $subjectKey,
        public string $body,
        public string $author,
        public string $createdAt,
        public string $context = '',
        public KnowledgeLifecycle $lifecycle = KnowledgeLifecycle::Contextual,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subject_type' => $this->subjectType->value,
            'subject_key' => $this->subjectKey,
            'body' => $this->body,
            'author' => $this->author,
            'created_at' => $this->createdAt,
            'context' => $this->context,
            'lifecycle' => $this->lifecycle->value,
            'kind' => 'note',
        ];
    }
}
