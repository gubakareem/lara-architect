<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Rendering;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;
use KarimAshraf\LaraArchitect\Architecture\Contracts\Renderer;
use RuntimeException;

/**
 * Reserved format for GitHub Code Scanning. Not implemented in v1.4 —
 * keeping the slot in the renderer registry so the CLI flag is stable.
 */
final class SarifRenderer implements Renderer
{
    public function format(): string
    {
        return 'sarif';
    }

    public function render(AnalysisResult $result, string $basePath = ''): string
    {
        throw new RuntimeException(
            'SARIF output is reserved for a future release. Use --format=json or --format=console.',
        );
    }
}
