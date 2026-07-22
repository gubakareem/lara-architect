<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\UseCases;

use KarimAshraf\LaraArchitect\Architecture\DependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\Hotspot;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;

/**
 * Layer counts and hotspot detection for an existing graph.
 * Full MetricCalculator plugins arrive in v1.4.1; this use case
 * keeps the current threshold-based hotspot behaviour.
 */
final class AnalyzeArchitecture
{
    /**
     * @param  array{public_methods?: int, constructor_dependencies?: int, file_lines?: int}  $thresholds
     * @return array{layers: array<string, int>, hotspots: list<Hotspot>}
     */
    public function execute(DependencyGraph $graph, LayerRegistry $layers, array $thresholds = []): array
    {
        return [
            'layers' => $this->layerCounts($graph, $layers),
            'hotspots' => $this->hotspots($graph, $thresholds),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function layerCounts(DependencyGraph $graph, LayerRegistry $layers): array
    {
        if ($layers->isEmpty()) {
            return [];
        }

        $counts = array_fill_keys($layers->names(), 0);

        foreach (array_keys($graph->nodes()) as $fqcn) {
            $layer = $layers->layerFor($fqcn);

            if ($layer !== null) {
                $counts[$layer->name]++;
            }
        }

        return $counts;
    }

    /**
     * @param  array{public_methods?: int, constructor_dependencies?: int, file_lines?: int}  $thresholds
     * @return list<Hotspot>
     */
    private function hotspots(DependencyGraph $graph, array $thresholds): array
    {
        $maxPublicMethods = $thresholds['public_methods'] ?? 8;
        $maxDependencies = $thresholds['constructor_dependencies'] ?? 5;
        $maxLines = $thresholds['file_lines'] ?? 300;

        $hotspots = [];

        foreach ($graph->nodes() as $file) {
            $publicMethods = count($file->linesMatching('/^\s*(?:final\s+)?public\s+(?:static\s+)?function\s+(?!__)/'));

            if ($publicMethods > $maxPublicMethods) {
                $hotspots[] = new Hotspot(
                    $file->path,
                    sprintf('%d public methods (max %d) — consider splitting responsibilities.', $publicMethods, $maxPublicMethods),
                );
            }

            if (preg_match('/function\s+__construct\s*\(([^)]*)\)/s', $file->contents, $matches) === 1) {
                $dependencies = substr_count($matches[1], '$');

                if ($dependencies > $maxDependencies) {
                    $hotspots[] = new Hotspot(
                        $file->path,
                        sprintf('%d constructor dependencies (max %d) — the class may be doing too much.', $dependencies, $maxDependencies),
                    );
                }
            }

            if (count($file->lines) > $maxLines) {
                $hotspots[] = new Hotspot(
                    $file->path,
                    sprintf('%d lines (max %d) — consider extracting collaborators.', count($file->lines), $maxLines),
                );
            }
        }

        return $hotspots;
    }
}
