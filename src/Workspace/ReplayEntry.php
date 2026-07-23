<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Chronological Replay entry — projection of Architecture Events (not analytics).
 */
final readonly class ReplayEntry
{
    public function __construct(
        public string $occurredAt,
        public string $type,
        public string $label,
        public string $tone,
        public array $detail = [],
        public ?EventCorrelation $correlation = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'occurred_at' => $this->occurredAt,
            'type' => $this->type,
            'label' => $this->label,
            'tone' => $this->tone,
            'detail' => $this->detail,
            'correlation' => $this->correlation?->toArray(),
        ];
    }
}
