<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Ownership context — not permissions, not team management.
 * Knowledge needs a home: notes have owners, rationales have reviewers, standards have maintainers.
 */
final readonly class ArchitectureOwnership
{
    public function __construct(
        public string $area,
        public string $ownedBy,
        public string $maintainedBy = '',
        public string $recordedAt = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'owned_by' => $this->ownedBy,
            'maintained_by' => $this->maintainedBy,
            'recorded_at' => $this->recordedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload, string $fallbackAt = ''): self
    {
        return new self(
            area: (string) ($payload['area'] ?? ''),
            ownedBy: (string) ($payload['owned_by'] ?? ''),
            maintainedBy: (string) ($payload['maintained_by'] ?? ''),
            recordedAt: (string) ($payload['recorded_at'] ?? $fallbackAt),
        );
    }
}
