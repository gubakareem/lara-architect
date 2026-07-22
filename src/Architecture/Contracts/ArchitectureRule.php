<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Contracts;

use KarimAshraf\LaraArchitect\Architecture\DependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;
use KarimAshraf\LaraArchitect\Architecture\Violation;

/**
 * Graph-only architecture rule. Never inspects PHP source — only nodes,
 * edges and layers. That keeps rules independent of the extractor.
 */
interface ArchitectureRule
{
    public function id(): string;

    /**
     * @return list<Violation>
     */
    public function evaluate(DependencyGraph $graph, LayerRegistry $layers): array;
}
