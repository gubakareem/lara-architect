<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Rejected / deferred option — answers "why not the obvious alternative?"
 * without reopening the full conversation.
 */
final readonly class DecisionAlternative
{
    public function __construct(
        public string $option,
        public AlternativeStatus $status,
        public string $reason,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'option' => $this->option,
            'status' => $this->status->value,
            'reason' => $this->reason,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            option: (string) ($payload['option'] ?? ''),
            status: AlternativeStatus::tryFrom((string) ($payload['status'] ?? 'rejected'))
                ?? AlternativeStatus::Rejected,
            reason: (string) ($payload['reason'] ?? ''),
        );
    }
}
