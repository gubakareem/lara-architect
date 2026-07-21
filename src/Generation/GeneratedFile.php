<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

/**
 * A file produced by a generator, not yet written to disk.
 */
final class GeneratedFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $contents,
        public readonly string $description,
    ) {}
}
