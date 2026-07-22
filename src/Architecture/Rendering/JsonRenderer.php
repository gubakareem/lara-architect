<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Rendering;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;
use KarimAshraf\LaraArchitect\Architecture\Contracts\Renderer;

final class JsonRenderer implements Renderer
{
    public function format(): string
    {
        return 'json';
    }

    public function render(AnalysisResult $result, string $basePath = ''): string
    {
        return json_encode($result->toArray($basePath), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}
