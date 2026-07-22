<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\UseCases;

use KarimAshraf\LaraArchitect\Architecture\Contracts\DependencyExtractor;
use KarimAshraf\LaraArchitect\Architecture\DependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\FileScanner;

/**
 * Builds a dependency graph for the given root + paths.
 */
final class BuildDependencyGraph
{
    public function __construct(
        private readonly DependencyExtractor $extractor,
    ) {}

    /**
     * @param  list<string>  $paths
     */
    public function execute(string $root, array $paths): DependencyGraph
    {
        $scanner = new FileScanner($root);
        $graph = new DependencyGraph;

        foreach ($scanner->scan($paths) as $file) {
            $graph->addFile($file);
            $graph->addEdges($this->extractor->extract($file));
        }

        return $graph;
    }
}
