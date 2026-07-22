<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

final readonly class LayerId
{
    public function __construct(public string $name) {}

    public static function of(string $name): self
    {
        return new self($name);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
