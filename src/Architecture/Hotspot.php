<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

final readonly class Hotspot
{
    public function __construct(
        public string $path,
        public string $message,
    ) {}
}
