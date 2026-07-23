<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;

/**
 * Phase 1.5 — related symbols and a small architecture neighborhood (not the full graph).
 */
final class ContextIntelligence
{
    /**
     * @param  callable(string): string  $relative
     * @return array{related: list<array<string, mixed>>, neighborhood: array<string, mixed>}
     */
    public function build(
        AnalysisResult $analysis,
        WorkspaceContext $context,
        LayerRegistry $layers,
        callable $relative,
    ): array {
        $focusFqcn = $this->resolveFocusFqcn($analysis, $context, $relative);

        if ($focusFqcn === null) {
            return [
                'related' => [],
                'neighborhood' => [
                    'focus' => $context->name,
                    'focus_layer' => null,
                    'layers' => [],
                    'edges' => [],
                ],
            ];
        }

        $relatedMap = [];
        $layerFlow = [];

        $focusLayer = $layers->layerFor($focusFqcn);
        if ($focusLayer !== null) {
            $layerFlow[$focusLayer->name] = true;
        }

        foreach ($analysis->graph->edges() as $edge) {
            $isOutbound = $edge->source->fqcn === $focusFqcn;
            $isInbound = $edge->target->fqcn === $focusFqcn;

            if (! $isOutbound && ! $isInbound) {
                continue;
            }

            $other = $isOutbound ? $edge->target : $edge->source;
            $file = $analysis->graph->fileFor($other);
            $otherLayer = $layers->layerFor($other);

            if ($otherLayer !== null) {
                $layerFlow[$otherLayer->name] = true;
            }

            $relatedMap[$other->fqcn] = [
                'name' => class_basename($other->fqcn),
                'fqcn' => $other->fqcn,
                'path' => $file !== null ? $relative($file->path) : null,
                'layer' => $otherLayer?->name,
                'relation' => $isOutbound ? 'depends_on' : 'depended_by',
            ];
        }

        $related = array_values($relatedMap);
        usort($related, static function (array $a, array $b): int {
            return strcmp((string) ($a['layer'] ?? ''), (string) ($b['layer'] ?? ''));
        });

        $orderedLayers = $this->orderLayers(array_keys($layerFlow), $focusLayer?->name);
        $edges = [];
        for ($i = 0; $i < count($orderedLayers) - 1; $i++) {
            $edges[] = [
                'from' => $orderedLayers[$i],
                'to' => $orderedLayers[$i + 1],
            ];
        }

        return [
            'related' => $related,
            'neighborhood' => [
                'focus' => class_basename($focusFqcn),
                'focus_layer' => $focusLayer?->name,
                'layers' => $orderedLayers,
                'edges' => $edges,
            ],
        ];
    }

    /**
     * @param  callable(string): string  $relative
     */
    private function resolveFocusFqcn(AnalysisResult $analysis, WorkspaceContext $context, callable $relative): ?string
    {
        if ($context->path !== null) {
            $needle = str_replace('\\', '/', $context->path);
            foreach ($analysis->graph->nodes() as $fqcn => $file) {
                $path = str_replace('\\', '/', $relative($file->path));
                if (str_ends_with($path, $needle) || str_ends_with($path, basename($needle))) {
                    return $fqcn;
                }
            }
        }

        foreach ($analysis->graph->nodes() as $fqcn => $file) {
            if (class_basename($fqcn) === $context->name) {
                return $fqcn;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $layers
     * @return list<string>
     */
    private function orderLayers(array $layers, ?string $focus): array
    {
        $preferred = ['Controller', 'Request', 'Service', 'Action', 'Repository', 'Model', 'Infrastructure'];
        $ordered = [];

        foreach ($preferred as $name) {
            if (in_array($name, $layers, true)) {
                $ordered[] = $name;
            }
        }

        foreach ($layers as $name) {
            if (! in_array($name, $ordered, true)) {
                $ordered[] = $name;
            }
        }

        if ($focus !== null && in_array($focus, $ordered, true)) {
            // Keep preferred order; focus is marked separately in payload.
        }

        return $ordered;
    }
}
