<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Bridge from conversation → durable Memory.
 * Journey ends as rationale (or explicitly no decision).
 * Alternatives preserve rejected options for future questions.
 */
final readonly class DecisionOutcome
{
    /**
     * @param  list<DecisionAlternative>  $alternatives
     */
    public function __construct(
        public string $decision,
        public string $result,
        public bool $futureReference = true,
        public ?string $rationaleId = null,
        public DecisionLifecycle $lifecycle = DecisionLifecycle::Accepted,
        public array $alternatives = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'decision' => $this->decision,
            'result' => $this->result,
            'future_reference' => $this->futureReference,
            'rationale_id' => $this->rationaleId,
            'lifecycle' => $this->lifecycle->value,
            'alternatives' => array_map(
                static fn (DecisionAlternative $a): array => $a->toArray(),
                $this->alternatives,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $alternatives = [];
        foreach ((array) ($payload['alternatives'] ?? []) as $row) {
            if (is_array($row)) {
                $alternatives[] = DecisionAlternative::fromPayload($row);
            }
        }

        return new self(
            decision: (string) ($payload['decision'] ?? ''),
            result: (string) ($payload['result'] ?? ''),
            futureReference: (bool) ($payload['future_reference'] ?? true),
            rationaleId: isset($payload['rationale_id']) ? (string) $payload['rationale_id'] : null,
            lifecycle: DecisionLifecycle::tryFrom((string) ($payload['lifecycle'] ?? 'accepted'))
                ?? DecisionLifecycle::Accepted,
            alternatives: $alternatives,
        );
    }
}
