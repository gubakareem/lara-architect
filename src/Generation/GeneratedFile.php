<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

/**
 * A file produced by a generator, not yet written to disk.
 *
 * When $merge is true and the path already exists, ModuleGenerator merges the
 * new contents into the existing file instead of skipping (used for lang/enums.php).
 */
final class GeneratedFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $contents,
        public readonly string $description,
        public readonly bool $merge = false,
    ) {}
}
