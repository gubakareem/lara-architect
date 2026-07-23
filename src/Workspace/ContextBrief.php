<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 12 — before touching a file / context: living architecture knowledge.
 */
final readonly class ContextBrief
{
    /**
     * @param  list<string>  $importantDecisions
     * @param  list<string>  $recentChanges
     */
    public function __construct(
        public string $context,
        public string $whyItExists,
        public array $importantDecisions,
        public array $recentChanges,
        public int $improvementCount = 0,
        public string $summary = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'why_this_exists' => $this->whyItExists,
            'important_decisions' => $this->importantDecisions,
            'recent_changes' => $this->recentChanges,
            'improvement_count' => $this->improvementCount,
            'summary' => $this->summary,
        ];
    }
}
