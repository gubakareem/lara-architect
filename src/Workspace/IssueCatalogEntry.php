<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Result of looking up curated education for a Finding.
 */
final readonly class IssueCatalogEntry
{
    /**
     * @param  list<WorkspaceAction>  $actions
     */
    public function __construct(
        public string $title,
        public IssueExplanation $explanation,
        public bool $safeFix,
        public array $actions,
    ) {}
}
