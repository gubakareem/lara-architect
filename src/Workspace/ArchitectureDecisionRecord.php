<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One architectural decision over time — not a conversation dump.
 */
final readonly class ArchitectureDecisionRecord
{
    /**
     * @param  list<DecisionAlternative>  $alternatives
     * @param  list<string>  $referencedContexts
     */
    public function __construct(
        public string $area,
        public string $period,
        public string $decision,
        public string $reason,
        public int $evidenceImprovements,
        public array $referencedContexts,
        public DecisionLifecycle $lifecycle,
        public array $alternatives = [],
        public ?string $conversationId = null,
        public ?string $rationaleId = null,
        public string $occurredAt = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'period' => $this->period,
            'decision' => $this->decision,
            'reason' => $this->reason,
            'evidence' => [
                'improvements' => $this->evidenceImprovements,
            ],
            'referenced' => [
                'contexts' => $this->referencedContexts,
                'count' => count($this->referencedContexts),
            ],
            'lifecycle' => $this->lifecycle->value,
            'alternatives' => array_map(
                static fn (DecisionAlternative $a): array => $a->toArray(),
                $this->alternatives,
            ),
            'conversation_id' => $this->conversationId,
            'rationale_id' => $this->rationaleId,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
