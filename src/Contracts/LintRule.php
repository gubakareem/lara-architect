<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Contracts;

use KarimAshraf\LaraArchitect\Analysis\ScannedFile;
use KarimAshraf\LaraArchitect\Analysis\Violation;

interface LintRule
{
    /**
     * Short kebab-case identifier shown next to each violation.
     */
    public function name(): string;

    /**
     * @return list<Violation>
     */
    public function check(ScannedFile $file): array;
}
