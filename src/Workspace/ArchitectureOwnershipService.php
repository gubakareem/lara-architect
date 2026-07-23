<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Record and project architecture ownership for an area.
 * Not ACLs — just "who owns / maintains this knowledge home."
 */
final class ArchitectureOwnershipService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
    ) {}

    public function record(
        string $projectRoot,
        string $area,
        string $ownedBy,
        string $maintainedBy = '',
    ): ArchitectureOwnership {
        $ownership = new ArchitectureOwnership(
            area: trim($area),
            ownedBy: trim($ownedBy),
            maintainedBy: trim($maintainedBy),
            recordedAt: gmdate('c'),
        );

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::OwnershipRecorded,
            $ownership->area !== '' ? $ownership->area : 'architecture',
            $ownership->toArray(),
        );

        return $ownership;
    }

    public function forArea(string $projectRoot, string $area): ?ArchitectureOwnership
    {
        $needle = strtolower(trim($area));
        if ($needle === '') {
            return null;
        }

        foreach (array_reverse($this->memory->allEvents($projectRoot, 2000)) as $event) {
            if ($event->type !== ArchitectureEventType::OwnershipRecorded) {
                continue;
            }
            $ownership = ArchitectureOwnership::fromPayload($event->payload, $event->occurredAt);
            if (strcasecmp($ownership->area, $area) === 0
                || str_contains(strtolower($ownership->area), $needle)
                || str_contains($needle, strtolower($ownership->area))) {
                return $ownership;
            }
        }

        return null;
    }
}
