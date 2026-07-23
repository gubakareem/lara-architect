<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * How identity became what it is — period transitions, not daily noise.
 */
final readonly class IdentityHistoryEntry
{
    public function __construct(
        public string $period,
        public string $style,
        public string $reason,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'style' => $this->style,
            'reason' => $this->reason,
        ];
    }
}
