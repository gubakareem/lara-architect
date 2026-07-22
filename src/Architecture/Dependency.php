<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * A typed edge: source depends on target.
 */
final readonly class Dependency
{
    public function __construct(
        public NodeId $source,
        public NodeId $target,
        public EdgeType $type,
        public int $line,
    ) {}
}
