<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

final readonly class RuleId
{
    public function __construct(public string $value) {}

    public static function of(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
