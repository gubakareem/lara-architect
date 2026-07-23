<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Shared string identity pattern (same philosophy as NodeId / RuleId / LayerId).
 */
abstract readonly class WorkspaceIdentity
{
    final public function __construct(public string $value) {}

    public static function of(string $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
