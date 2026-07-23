<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 14 read model — conversations attached to architecture objects.
 * Not a global chat. Questions = what we know; Conversations = what we think about it.
 */
final readonly class ArchitectureConversations
{
    /**
     * @param  list<ArchitectureConversation>  $conversations
     */
    public function __construct(
        public string $question,
        public string $summary,
        public array $conversations,
        public string $subjectKey = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'summary' => $this->summary,
            'subject_key' => $this->subjectKey,
            'conversations' => array_map(
                static fn (ArchitectureConversation $c): array => $c->toArray(),
                $this->conversations,
            ),
            'count' => count($this->conversations),
        ];
    }
}
