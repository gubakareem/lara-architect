<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Internal graph: nodes (classes) and typed dependency edges.
 * Not part of the v1.x public API — may be rewritten before v2.
 */
final class DependencyGraph
{
    /** @var array<string, ArchitectureFile> */
    private array $nodes = [];

    /** @var list<Dependency> */
    private array $edges = [];

    public function addFile(ArchitectureFile $file): void
    {
        $id = $file->nodeId();

        if ($id !== null) {
            $this->nodes[$id->fqcn] = $file;
        }
    }

    /**
     * @param  list<Dependency>  $edges
     */
    public function addEdges(array $edges): void
    {
        $this->edges = [...$this->edges, ...$edges];
    }

    /**
     * @return array<string, ArchitectureFile>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return list<Dependency>
     */
    public function edges(): array
    {
        return $this->edges;
    }

    public function fileFor(NodeId|string $class): ?ArchitectureFile
    {
        $fqcn = $class instanceof NodeId ? $class->fqcn : ltrim($class, '\\');

        return $this->nodes[$fqcn] ?? null;
    }
}
