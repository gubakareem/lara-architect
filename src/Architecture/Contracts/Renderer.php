<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Contracts;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;

/**
 * Turns an AnalysisResult into a concrete representation.
 * Console and JSON ship in v1.4; SARIF / HTML / GitHub are reserved.
 */
interface Renderer
{
    public function format(): string;

    public function render(AnalysisResult $result, string $basePath = ''): string;
}
