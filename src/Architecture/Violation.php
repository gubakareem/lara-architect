<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

final readonly class Violation
{
    public function __construct(
        public RuleId $rule,
        public string $path,
        public int $line,
        public string $message,
        public ?NodeId $source = null,
        public ?NodeId $target = null,
    ) {}
}
