<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

final readonly class TimelineEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public TimelineEventType $type,
        public string $occurredAt,
        public array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function of(TimelineEventType $type, array $payload = []): self
    {
        return new self($type, gmdate('c'), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
        ];
    }
}
