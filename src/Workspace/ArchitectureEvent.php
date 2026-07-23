<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One immutable architecture fact. Events ≠ Sessions.
 * Correlation enables Replay: File → Change → Execution → Proposal → Issue → Finding.
 */
final readonly class ArchitectureEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ArchitectureEventId $id,
        public ArchitectureEventType $type,
        public string $context,
        public string $occurredAt,
        public EventCorrelation $correlation = new EventCorrelation,
        public array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(
        ArchitectureEventType $type,
        string $context,
        array $payload = [],
        ?EventCorrelation $correlation = null,
        ?string $occurredAt = null,
    ): self {
        $corr = ($correlation ?? EventCorrelation::empty())->mergePayload($payload);

        return new self(
            id: ArchitectureEventId::of('evt_'.bin2hex(random_bytes(8))),
            type: $type,
            context: $context,
            occurredAt: $occurredAt ?? gmdate('c'),
            correlation: $corr,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $correlation = isset($data['correlation']) && is_array($data['correlation'])
            ? EventCorrelation::fromArray($data['correlation'])->mergePayload($payload)
            : EventCorrelation::empty()->mergePayload($payload);

        return new self(
            id: ArchitectureEventId::of((string) ($data['id'] ?? $data['event_id'] ?? 'evt_unknown')),
            type: ArchitectureEventType::from((string) ($data['type'] ?? $data['event'] ?? '')),
            context: (string) ($data['context'] ?? ''),
            occurredAt: (string) ($data['occurred_at'] ?? $data['timestamp'] ?? gmdate('c')),
            correlation: $correlation,
            payload: $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'event_id' => (string) $this->id,
            'type' => $this->type->value,
            'event' => $this->type->value,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt,
            'timestamp' => $this->occurredAt,
            'correlation' => $this->correlation->toArray(),
            'issue_id' => $this->correlation->issueId,
            'proposal_id' => $this->correlation->proposalId,
            'execution_id' => $this->correlation->executionId,
            'session_id' => $this->correlation->sessionId,
            'payload' => $this->payload,
        ];
    }
}
