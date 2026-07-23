<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Point-in-time architecture snapshot so Replay has a “before the journey” reference.
 */
final readonly class ArchitectureBaseline
{
    public const SCHEMA_VERSION = '1.0';

    public function __construct(
        public string $capturedAt,
        public int $health,
        public int $violations,
        public int $dependencies,
        public string $project,
        public string $schemaVersion = self::SCHEMA_VERSION,
    ) {}

    public static function fromSnapshot(WorkspaceSnapshot $snapshot): self
    {
        $health = $snapshot->health->score !== null
            ? (int) round($snapshot->health->score)
            : match ($snapshot->health->band) {
                'Excellent' => 98,
                'Good' => 91,
                'Needs Attention' => 78,
                default => 55,
            };

        $dependencies = (int) ($snapshot->metrics['dependency_edges']
            ?? $snapshot->metrics['dependencies']
            ?? $snapshot->neighborhood['edge_count']
            ?? count($snapshot->related));

        return new self(
            capturedAt: gmdate('c'),
            health: $health,
            violations: count($snapshot->issues),
            dependencies: $dependencies,
            project: $snapshot->project,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            capturedAt: (string) ($data['captured_at'] ?? gmdate('c')),
            health: (int) ($data['health'] ?? 0),
            violations: (int) ($data['violations'] ?? 0),
            dependencies: (int) ($data['dependencies'] ?? 0),
            project: (string) ($data['project'] ?? ''),
            schemaVersion: (string) ($data['schema_version'] ?? self::SCHEMA_VERSION),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'captured_at' => $this->capturedAt,
            'health' => $this->health,
            'violations' => $this->violations,
            'dependencies' => $this->dependencies,
            'project' => $this->project,
        ];
    }
}
