<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Identity of a class (or class-like) node in the dependency graph.
 */
final readonly class NodeId
{
    private function __construct(public string $fqcn) {}

    public static function fromClass(string $fqcn): self
    {
        return new self(ltrim($fqcn, '\\'));
    }

    public function equals(self $other): bool
    {
        return $this->fqcn === $other->fqcn;
    }

    public function __toString(): string
    {
        return $this->fqcn;
    }
}
