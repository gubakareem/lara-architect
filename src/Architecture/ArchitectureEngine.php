<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

use KarimAshraf\LaraArchitect\Architecture\Contracts\ArchitectureRule;
use KarimAshraf\LaraArchitect\Architecture\Contracts\DependencyExtractor;
use KarimAshraf\LaraArchitect\Architecture\Contracts\RulePack;
use KarimAshraf\LaraArchitect\Architecture\Extraction\RegexExtractor;
use KarimAshraf\LaraArchitect\Architecture\Packs\LaravelRulePack;
use KarimAshraf\LaraArchitect\Architecture\UseCases\AnalyzeArchitecture;
use KarimAshraf\LaraArchitect\Architecture\UseCases\BuildDependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\UseCases\LintArchitecture;

/**
 * Public façade for the architecture engine. Framework-agnostic:
 *
 *     $engine = ArchitectureEngine::create();
 *     $result = $engine->analyze('/path/to/project', ['app']);
 *
 * Artisan commands are thin adapters over this API.
 */
final class ArchitectureEngine
{
    public function __construct(
        private readonly BuildDependencyGraph $buildGraph,
        private readonly LintArchitecture $lint,
        private readonly AnalyzeArchitecture $analyze,
        private readonly LayerRegistry $layers,
        private readonly array $rules,
        private readonly array $thresholds = [],
    ) {}

    /**
     * @param  list<ArchitectureRule>|null  $rules
     * @param  array{public_methods?: int, constructor_dependencies?: int, file_lines?: int}  $thresholds
     */
    public static function create(
        ?DependencyExtractor $extractor = null,
        ?LayerRegistry $layers = null,
        ?array $rules = null,
        array $thresholds = [],
        ?RulePack $pack = null,
    ): self {
        $pack ??= new LaravelRulePack;
        $extractor ??= new RegexExtractor;

        return new self(
            new BuildDependencyGraph($extractor),
            new LintArchitecture,
            new AnalyzeArchitecture,
            $layers ?? $pack->layers(),
            $rules ?? $pack->rules(),
            $thresholds,
        );
    }

    /**
     * Full analysis: graph + violations + layer counts + hotspots.
     *
     * @param  list<string>  $paths
     */
    public function analyze(string $root, array $paths = ['app']): AnalysisResult
    {
        $graph = $this->buildGraph->execute($root, $paths);
        $violations = $this->lint->execute($graph, $this->layers, $this->rules);
        $report = $this->analyze->execute($graph, $this->layers, $this->thresholds);

        return AnalysisResult::from(
            filesScanned: count($graph->nodes()),
            violations: $violations,
            layerCounts: $report['layers'],
            hotspots: $report['hotspots'],
            graph: $graph,
        );
    }

    /**
     * Lint-focused run (same result shape; callers typically ignore hotspots).
     *
     * @param  list<string>  $paths
     */
    public function lint(string $root, array $paths = ['app']): AnalysisResult
    {
        return $this->analyze($root, $paths);
    }

    /**
     * @param  list<string>  $paths
     */
    public function graph(string $root, array $paths = ['app']): DependencyGraph
    {
        return $this->buildGraph->execute($root, $paths);
    }

    public function layers(): LayerRegistry
    {
        return $this->layers;
    }
}
