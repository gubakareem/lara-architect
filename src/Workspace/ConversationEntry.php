<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One reasoning step inside an Architecture Conversation.
 */
final readonly class ConversationEntry
{
    public function __construct(
        public string $id,
        public ConversationEntryType $type,
        public string $content,
        public string $author,
        public string $createdAt,
        public string $conversationId = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'content' => $this->content,
            'author' => $this->author,
            'created_at' => $this->createdAt,
            'conversation_id' => $this->conversationId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload, string $fallbackAt = ''): self
    {
        return new self(
            id: (string) ($payload['id'] ?? 'entry_unknown'),
            type: ConversationEntryType::tryFrom((string) ($payload['type'] ?? 'opinion'))
                ?? ConversationEntryType::Opinion,
            content: (string) ($payload['content'] ?? ''),
            author: (string) ($payload['author'] ?? 'developer'),
            createdAt: (string) ($payload['created_at'] ?? $fallbackAt),
            conversationId: (string) ($payload['conversation_id'] ?? ''),
        );
    }
}
