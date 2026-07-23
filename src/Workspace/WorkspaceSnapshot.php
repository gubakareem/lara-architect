<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Immutable Workspace read model. Surfaces (CLI, React, Debugbar, VS Code) consume this — not the DB.
 *
 * schema_version is SemVer for the *payload shape* so adapters can evolve safely.
 * 1.1 adds breadcrumb, structured impact, related context, and neighborhood map.
 */
final readonly class WorkspaceSnapshot
{
    public const SCHEMA_VERSION = '1.1';

    /**
     * @param  list<WorkspaceIssue>  $issues
     * @param  list<WorkspaceAction>  $actions
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>  $today
     * @param  list<array<string, mixed>>  $related
     * @param  array<string, mixed>  $neighborhood
     */
    public function __construct(
        public WorkspaceId $id,
        public string $project,
        public WorkspaceContext $context,
        public WorkspaceHealth $health,
        public array $issues,
        public array $actions,
        public array $metrics = [],
        public array $today = [],
        public array $related = [],
        public array $neighborhood = [],
        public string $schemaVersion = self::SCHEMA_VERSION,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'workspace' => [
                'id' => (string) $this->id,
                'project' => $this->project,
                'health' => $this->health->toArray(),
                'today' => $this->today,
                'metrics' => $this->metrics,
            ],
            'context' => $this->context->toArray(),
            'issues' => array_map(
                static fn (WorkspaceIssue $issue): array => $issue->toArray(),
                $this->issues,
            ),
            'actions' => array_map(
                static fn (WorkspaceAction $action): array => $action->toArray(),
                $this->actions,
            ),
            'related' => $this->related,
            'neighborhood' => $this->neighborhood,
        ];
    }

    public function issueById(string|IssueId $id): ?WorkspaceIssue
    {
        $needle = (string) $id;

        foreach ($this->issues as $issue) {
            if ((string) $issue->id === $needle) {
                return $issue;
            }
        }

        return null;
    }

    public function primaryIssue(): ?WorkspaceIssue
    {
        foreach ($this->issues as $issue) {
            if ($issue->primary) {
                return $issue;
            }
        }

        return $this->issues[0] ?? null;
    }
}
