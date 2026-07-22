<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Contracts;

use KarimAshraf\LaraArchitect\Architecture\ArchitectureFile;
use KarimAshraf\LaraArchitect\Architecture\Dependency;

/**
 * Extracts typed dependency edges from a source file.
 * Implementations: RegexExtractor (v1), AstExtractor / PhpStanExtractor (v2).
 */
interface DependencyExtractor
{
    /**
     * @return list<Dependency>
     */
    public function extract(ArchitectureFile $file): array;
}
