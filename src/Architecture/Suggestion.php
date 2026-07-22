<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Reserved value object for the suggestion engine (v1.6).
 */
final readonly class Suggestion
{
    public function __construct(
        public string $title,
        public string $detail,
        public ?string $command = null,
    ) {}
}
