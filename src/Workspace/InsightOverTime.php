<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Structured movement for explainability — before/after from evidence, not prose-only.
 */
final readonly class InsightOverTime
{
    public function __construct(
        public string $before,
        public string $after,
        public ?string $summary = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'before' => $this->before,
            'after' => $this->after,
            'summary' => $this->summary,
        ];
    }
}
