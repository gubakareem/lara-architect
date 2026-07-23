<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 11 — permanent human rationale for a decision that code alone cannot explain.
 * Answers: "Why does this architecture exist this way?"
 * Lifecycle: permanent — keep distinct from ArchitectureNote.
 */
final readonly class ArchitectureRationale
{
    public function __construct(
        public string $id,
        public string $question,
        public string $reason,
        public string $author,
        public string $createdAt,
        public string $subjectKey = '',
        public CollaborationSubject $subjectType = CollaborationSubject::Decision,
        public string $context = '',
        public string $tradeoff = '',
        public KnowledgeLifecycle $lifecycle = KnowledgeLifecycle::Permanent,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'reason' => $this->reason,
            'tradeoff' => $this->tradeoff,
            'author' => $this->author,
            'created_at' => $this->createdAt,
            'subject_type' => $this->subjectType->value,
            'subject_key' => $this->subjectKey,
            'context' => $this->context,
            'lifecycle' => $this->lifecycle->value,
            'kind' => 'rationale',
        ];
    }
}
