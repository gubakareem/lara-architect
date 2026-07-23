<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Evidence behind an intelligence projection — immutable counts from Events.
 * Confidence of the insight is derived from this, not stored as a guess.
 */
final readonly class InsightEvidence
{
    public function __construct(
        public int $events,
        public int $contexts,
        public string $timeRange,
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'events' => $this->events,
            'contexts' => $this->contexts,
            'time_range' => $this->timeRange,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}
