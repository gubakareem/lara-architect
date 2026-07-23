<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Developer-facing improvement opportunity — built from Finding(s) + IssueCatalog education.
 */
final readonly class WorkspaceIssue
{
    /**
     * @param  list<Finding>  $findings
     * @param  list<WorkspaceAction>  $actions
     */
    public function __construct(
        public IssueId $id,
        public string $title,
        public string $severity,
        public IssueExplanation $explanation,
        public bool $safeFix,
        public array $findings,
        public array $actions,
        public string $path = '',
        public int $line = 0,
        public bool $primary = false,
    ) {}

    public function impactOverall(): string
    {
        return $this->explanation->impact->overall;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'severity' => $this->severity,
            'impact' => $this->explanation->impact->overall,
            'primary' => $this->primary,
            'explanation' => $this->explanation->toArray(),
            'safe_fix' => $this->safeFix,
            'path' => $this->path,
            'line' => $this->line,
            'findings' => array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $this->findings,
            ),
            'actions' => array_map(
                static fn (WorkspaceAction $action): array => $action->toArray(),
                $this->actions,
            ),
        ];
    }

    public function withPrimary(bool $primary = true): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->severity,
            $this->explanation,
            $this->safeFix,
            $this->findings,
            $this->actions,
            $this->path,
            $this->line,
            $primary,
        );
    }
}
