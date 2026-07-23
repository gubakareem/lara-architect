<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 14 — Architecture Conversation container.
 * Journey of reasoning; Rationale remains durable knowledge.
 */
final readonly class ArchitectureConversation
{
    /**
     * @param  list<ConversationEntry>  $entries
     */
    public function __construct(
        public string $id,
        public string $context,
        public string $topic,
        public DecisionLifecycle $status,
        public ConversationSubject $subjectType,
        public string $subjectKey,
        public array $entries = [],
        public ?DecisionOutcome $outcome = null,
        public string $startedAt = '',
        public string $closedAt = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'context' => $this->context,
            'topic' => $this->topic,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subject_type' => $this->subjectType->value,
            'subject_key' => $this->subjectKey,
            'entries' => array_map(
                static fn (ConversationEntry $entry): array => $entry->toArray(),
                $this->entries,
            ),
            'outcome' => $this->outcome?->toArray(),
            'started_at' => $this->startedAt,
            'closed_at' => $this->closedAt,
            'entry_count' => count($this->entries),
        ];
    }
}
