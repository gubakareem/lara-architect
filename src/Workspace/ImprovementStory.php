<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Story — explain a change, not list events.
 *
 * Problem → Decision → Change → Proof → Result
 */
final readonly class ImprovementStory
{
    public function __construct(
        public string $context,
        public string $problem,
        public string $decision,
        public string $change,
        public string $proof,
        public string $result,
        public EventCorrelation $correlation,
        public ?string $occurredAt = null,
        public ?int $healthBefore = null,
        public ?int $healthAfter = null,
    ) {}

    /**
     * Flat strings for UI + nested Problem→Result shape for Decision Memory consumers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $delta = ($this->healthBefore !== null && $this->healthAfter !== null)
            ? $this->healthAfter - $this->healthBefore
            : null;

        return [
            'context' => $this->context,
            'problem' => $this->problem,
            'decision' => $this->decision,
            'change' => $this->change,
            'proof' => $this->proof,
            'result' => $this->result,
            'correlation' => $this->correlation->toArray(),
            'occurred_at' => $this->occurredAt,
            'health_before' => $this->healthBefore,
            'health_after' => $this->healthAfter,
            'health_delta' => $delta,
            // Nested read model (documentation generated from reality)
            'story' => [
                'problem' => ['title' => $this->problem],
                'decision' => ['title' => $this->decision],
                'change' => ['summary' => $this->change],
                'proof' => ['verification' => $this->proof],
                'result' => [
                    'summary' => $this->result,
                    'health_delta' => $delta,
                ],
            ],
        ];
    }
}
