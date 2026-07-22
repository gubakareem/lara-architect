<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Rules;

use KarimAshraf\LaraArchitect\Architecture\Contracts\ArchitectureRule;
use KarimAshraf\LaraArchitect\Architecture\DependencyGraph;
use KarimAshraf\LaraArchitect\Architecture\EdgeType;
use KarimAshraf\LaraArchitect\Architecture\LayerId;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;
use KarimAshraf\LaraArchitect\Architecture\RuleId;
use KarimAshraf\LaraArchitect\Architecture\Violation;

/**
 * Declarative layer dependency rule supporting allow and/or deny lists.
 *
 *     new LayerDependencyRule('Controller', deny: ['Model', 'Repository'])
 *     new LayerDependencyRule('Model', allow: ['Model'])
 */
final class LayerDependencyRule implements ArchitectureRule
{
    /**
     * @param  list<string>  $allow
     * @param  list<string>  $deny
     * @param  list<EdgeType>|null  $edges  Restrict to these edge types when set.
     */
    public function __construct(
        private readonly string $from,
        private readonly array $allow = [],
        private readonly array $deny = [],
        private readonly ?array $edges = null,
        private readonly string $ruleId = 'layer-dependency',
    ) {}

    public function id(): string
    {
        return $this->ruleId;
    }

    public function evaluate(DependencyGraph $graph, LayerRegistry $layers): array
    {
        if ($layers->isEmpty()) {
            return [];
        }

        $from = LayerId::of($this->from);
        $violations = [];
        $seen = [];

        foreach ($graph->edges() as $edge) {
            $sourceLayer = $layers->layerFor($edge->source);
            $targetLayer = $layers->layerFor($edge->target);

            if ($sourceLayer === null || $targetLayer === null || $sourceLayer->equals($targetLayer)) {
                continue;
            }

            if (! $sourceLayer->equals($from)) {
                continue;
            }

            if ($this->edges !== null && ! in_array($edge->type, $this->edges, true)) {
                continue;
            }

            $denied = $this->deny !== [] && in_array($targetLayer->name, $this->deny, true);
            $notAllowed = $this->allow !== [] && ! in_array($targetLayer->name, $this->allow, true);

            if (! $denied && ! $notAllowed) {
                continue;
            }

            $key = $edge->source->fqcn.'|'.$edge->target->fqcn.'|'.$edge->type->value;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $file = $graph->fileFor($edge->source);

            if ($file === null) {
                continue;
            }

            $violations[] = new Violation(
                RuleId::of($this->ruleId),
                $file->path,
                $edge->line,
                sprintf(
                    '%s [%s] must not depend on %s [%s] (%s)%s.',
                    $sourceLayer->name,
                    $edge->source->fqcn,
                    $targetLayer->name,
                    $edge->target->fqcn,
                    $edge->type->value,
                    $notAllowed
                        ? sprintf(' — %s may only depend on: %s', $sourceLayer->name, implode(', ', $this->allow))
                        : '',
                ),
                $edge->source,
                $edge->target,
            );
        }

        return $violations;
    }
}
