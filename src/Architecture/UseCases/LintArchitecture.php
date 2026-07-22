<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\UseCases;

use KarimAshraf\LaraArchitect\Architecture\Contracts\ArchitectureRule;
use KarimAshraf\LaraArchitect\Architecture\DependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;
use KarimAshraf\LaraArchitect\Architecture\Violation;

/**
 * Evaluates architecture rules against a graph. Rules never see source files.
 */
final class LintArchitecture
{
    /**
     * @param  list<ArchitectureRule>  $rules
     * @return list<Violation>
     */
    public function execute(DependencyGraph $graph, LayerRegistry $layers, array $rules): array
    {
        $violations = [];

        foreach ($rules as $rule) {
            $violations = [...$violations, ...$rule->evaluate($graph, $layers)];
        }

        return $violations;
    }
}
