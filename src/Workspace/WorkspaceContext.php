<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * What the developer is currently improving. The Workspace always revolves around this.
 */
final readonly class WorkspaceContext
{
    /**
     * @param  list<array{label: string, type: string}>  $breadcrumb
     */
    public function __construct(
        public ContextId $id,
        public WorkspaceContextType $type,
        public string $name,
        public ?string $path = null,
        public int $issueCount = 0,
        public int $suggestionCount = 0,
        public array $breadcrumb = [],
    ) {}

    public static function project(string $name = 'Project'): self
    {
        $breadcrumb = (new ContextBreadcrumb)->fromPath(null, $name, WorkspaceContextType::Project);

        return new self(
            ContextId::of('project:'.$name),
            WorkspaceContextType::Project,
            $name,
            breadcrumb: $breadcrumb,
        );
    }

    public static function file(string $name, string $path): self
    {
        $breadcrumb = (new ContextBreadcrumb)->fromPath($path, $name, WorkspaceContextType::File);

        return new self(
            ContextId::of('file:'.str_replace('\\', '/', $path)),
            WorkspaceContextType::File,
            $name,
            $path,
            breadcrumb: $breadcrumb,
        );
    }

    public static function module(string $name): self
    {
        $breadcrumb = (new ContextBreadcrumb)->fromPath(null, $name, WorkspaceContextType::Module);

        return new self(
            ContextId::of('module:'.$name),
            WorkspaceContextType::Module,
            $name,
            breadcrumb: $breadcrumb,
        );
    }

    public function withCounts(int $issues, int $suggestions): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->name,
            $this->path,
            $issues,
            $suggestions,
            $this->breadcrumb,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type->value,
            'name' => $this->name,
            'path' => $this->path,
            'issues' => $this->issueCount,
            'suggestions' => $this->suggestionCount,
            'breadcrumb' => $this->breadcrumb,
        ];
    }
}
