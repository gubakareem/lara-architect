<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Immutable outcome of one engine run. Renderers consume this; nothing mutates it.
 */
final readonly class AnalysisResult
{
    /**
     * @param  list<Violation>  $violations
     * @param  array<string, int>  $layerCounts
     * @param  list<Hotspot>  $hotspots
     * @param  list<Metric>  $metrics
     */
    public function __construct(
        public int $filesScanned,
        public array $violations,
        public array $layerCounts,
        public array $hotspots,
        public DependencyGraph $graph,
        public array $metrics = [],
        public ?float $healthScore = null,
    ) {}

    /**
     * @param  list<Violation>  $violations
     * @param  array<string, int>  $layerCounts
     * @param  list<Hotspot>  $hotspots
     * @param  list<Metric>  $metrics
     */
    public static function from(
        int $filesScanned,
        array $violations,
        array $layerCounts,
        array $hotspots,
        DependencyGraph $graph,
        array $metrics = [],
        ?float $healthScore = null,
    ): self {
        return new self(
            $filesScanned,
            $violations,
            $layerCounts,
            $hotspots,
            $graph,
            $metrics,
            $healthScore,
        );
    }

    public function withViolations(array $violations): self
    {
        return new self(
            $this->filesScanned,
            $violations,
            $this->layerCounts,
            $this->hotspots,
            $this->graph,
            $this->metrics,
            $this->healthScore,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(string $basePath = ''): array
    {
        $relative = static function (string $path) use ($basePath): string {
            if ($basePath === '') {
                return str_replace('\\', '/', $path);
            }

            $trimmed = str_replace([$basePath.DIRECTORY_SEPARATOR, $basePath.'/'], '', $path);

            return str_replace('\\', '/', $trimmed);
        };

        return [
            'files_scanned' => $this->filesScanned,
            'violations' => array_map(static fn (Violation $v): array => [
                'rule' => (string) $v->rule,
                'path' => $relative($v->path),
                'line' => $v->line,
                'message' => $v->message,
                'source' => $v->source?->fqcn,
                'target' => $v->target?->fqcn,
            ], $this->violations),
            'layers' => $this->layerCounts,
            'hotspots' => array_map(static fn (Hotspot $h): array => [
                'path' => $relative($h->path),
                'message' => $h->message,
            ], $this->hotspots),
            'metrics' => array_map(static fn (Metric $m): array => [
                'name' => $m->name,
                'value' => $m->value,
                'unit' => $m->unit,
            ], $this->metrics),
            'health_score' => $this->healthScore,
        ];
    }
}
