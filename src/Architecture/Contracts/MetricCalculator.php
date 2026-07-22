<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Contracts;

use KarimAshraf\LaraArchitect\Architecture\DependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\Metric;

/**
 * Reserved extension point for pluggable metrics (v1.4.1+).
 */
interface MetricCalculator
{
    public function name(): string;

    public function calculate(DependencyGraph $graph): Metric;
}
