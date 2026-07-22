<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Rendering;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;
use KarimAshraf\LaraArchitect\Architecture\Contracts\Renderer;
use KarimAshraf\LaraArchitect\Architecture\Hotspot;
use KarimAshraf\LaraArchitect\Architecture\Violation;

/**
 * Plain-text console renderer. The Artisan command may still use
 * Laravel components for colour; this renderer stays framework-free
 * and is the canonical text representation (also used in tests).
 */
final class ConsoleRenderer implements Renderer
{
    public function format(): string
    {
        return 'console';
    }

    public function render(AnalysisResult $result, string $basePath = ''): string
    {
        $lines = [
            sprintf('Scanned %d class(es).', $result->filesScanned),
        ];

        if ($result->layerCounts !== []) {
            $lines[] = '';
            $lines[] = 'Layers:';

            foreach ($result->layerCounts as $layer => $count) {
                $lines[] = sprintf('  %-20s %d', $layer, $count);
            }
        }

        if ($result->violations !== []) {
            $lines[] = '';
            $lines[] = sprintf('Violations (%d):', count($result->violations));

            foreach ($result->violations as $violation) {
                $lines[] = $this->formatViolation($violation, $basePath);
            }
        } else {
            $lines[] = '';
            $lines[] = 'No architecture violations.';
        }

        if ($result->hotspots !== []) {
            $lines[] = '';
            $lines[] = sprintf('Hotspots (%d):', count($result->hotspots));

            foreach ($result->hotspots as $hotspot) {
                $lines[] = $this->formatHotspot($hotspot, $basePath);
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function formatViolation(Violation $violation, string $basePath): string
    {
        return sprintf(
            '  %s:%d [%s] %s',
            $this->relative($violation->path, $basePath),
            $violation->line,
            $violation->rule,
            $violation->message,
        );
    }

    private function formatHotspot(Hotspot $hotspot, string $basePath): string
    {
        return sprintf('  %s — %s', $this->relative($hotspot->path, $basePath), $hotspot->message);
    }

    private function relative(string $path, string $basePath): string
    {
        if ($basePath === '') {
            return str_replace('\\', '/', $path);
        }

        return str_replace('\\', '/', str_replace([$basePath.DIRECTORY_SEPARATOR, $basePath.'/'], '', $path));
    }
}
