<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Memory concept: was this change intentional evolution?
 * Distinguishes intentional evolution from accidental drift.
 */
final readonly class ArchitectureChangeIntent
{
    public function __construct(
        public string $area,
        public string $intent,
        public string $expectedDirection,
        public ChangeIntentSource $createdFrom,
        public ?ArchitectureConcept $concept = null,
        public ?string $issueId = null,
        public ?string $occurredAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'intent' => $this->intent,
            'expected_direction' => $this->expectedDirection,
            'created_from' => $this->createdFrom->value,
            'concept' => $this->concept?->toArray(),
            'issue_id' => $this->issueId,
            'occurred_at' => $this->occurredAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload, string $occurredAt = ''): self
    {
        $source = ChangeIntentSource::tryFrom((string) ($payload['created_from'] ?? 'manual'))
            ?? ChangeIntentSource::Manual;

        $concept = null;
        if (isset($payload['concept']) && is_array($payload['concept'])) {
            $concept = new ArchitectureConcept(
                (string) ($payload['concept']['id'] ?? 'custom'),
                (string) ($payload['concept']['label'] ?? 'Unknown'),
                isset($payload['concept']['aliases']) && is_array($payload['concept']['aliases'])
                    ? array_values(array_map('strval', $payload['concept']['aliases']))
                    : [],
            );
        } else {
            $conceptId = (string) ($payload['concept_id'] ?? '');
            $conceptLabel = is_string($payload['concept'] ?? null) ? (string) $payload['concept'] : '';
            if ($conceptId !== '' || $conceptLabel !== '') {
                $concept = new ArchitectureConcept(
                    $conceptId !== '' ? $conceptId : 'custom',
                    $conceptLabel !== '' ? $conceptLabel : $conceptId,
                );
            }
        }

        return new self(
            area: (string) ($payload['area'] ?? 'unknown'),
            intent: (string) ($payload['intent'] ?? 'improve_architecture'),
            expectedDirection: (string) ($payload['expected_direction'] ?? ''),
            createdFrom: $source,
            concept: $concept,
            issueId: isset($payload['issue_id']) && is_scalar($payload['issue_id'])
                ? (string) $payload['issue_id']
                : null,
            occurredAt: $occurredAt !== '' ? $occurredAt : (string) ($payload['occurred_at'] ?? gmdate('c')),
        );
    }
}
