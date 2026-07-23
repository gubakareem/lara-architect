<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One append-only fact on a ChangeExecution.
 */
final readonly class ExecutionEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ExecutionEventType $type,
        public string $occurredAt,
        public array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function of(ExecutionEventType $type, array $payload = []): self
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
