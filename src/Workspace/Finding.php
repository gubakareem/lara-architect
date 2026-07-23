<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Technical fact from the engine (import, dependency, hotspot threshold, …).
 * One Finding can later map to multiple Issues; today mapping is typically 1:1.
 */
final readonly class Finding
{
    public function __construct(
        public FindingId $id,
        public string $kind,
        public string $summary,
        public string $path,
        public int $line,
        public string $rule,
        public ?string $sourceFqcn = null,
        public ?string $targetFqcn = null,
        public string $rawMessage = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'kind' => $this->kind,
            'summary' => $this->summary,
            'path' => $this->path,
            'line' => $this->line,
            'rule' => $this->rule,
            'source' => $this->sourceFqcn,
            'target' => $this->targetFqcn,
            'message' => $this->rawMessage,
        ];
    }
}
