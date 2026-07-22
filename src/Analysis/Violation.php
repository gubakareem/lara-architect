<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis;

final class Violation
{
    public function __construct(
        public readonly string $rule,
        public readonly string $path,
        public readonly int $line,
        public readonly string $message,
    ) {}
}
