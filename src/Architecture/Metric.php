<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * A named numeric measurement. Health scoring (v1.4.1) consumes these.
 */
final readonly class Metric
{
    public function __construct(
        public string $name,
        public float|int $value,
        public string $unit = '',
    ) {}
}
