<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Recent improvement card for History panel.
 */
final readonly class HistoryImprovement
{
    public function __construct(
        public string $title,
        public string $occurredAt,
        public ?string $sessionId = null,
        public ?int $healthBefore = null,
        public ?int $healthAfter = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'occurred_at' => $this->occurredAt,
            'session_id' => $this->sessionId,
            'health_before' => $this->healthBefore,
            'health_after' => $this->healthAfter,
        ];
    }
}
