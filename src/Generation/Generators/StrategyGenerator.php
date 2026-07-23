<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * GoF Strategy: interchangeable algorithms behind one interface, selected
 * at runtime by a context — not to be confused with business "strategies"
 * living inside services.
 */
class StrategyGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('strategy').'\\'.$blueprint->pluralModel();
        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
        ];

        return [
            $this->classFile($namespace, $blueprint->model().'Strategy', $this->stubs->render('strategy/contract', $replacements), 'Strategy contract'),
            $this->classFile($namespace, 'Default'.$blueprint->model().'Strategy', $this->stubs->render('strategy/default', $replacements), 'Default strategy'),
            $this->classFile($namespace, 'Alternative'.$blueprint->model().'Strategy', $this->stubs->render('strategy/alternative', $replacements), 'Alternative strategy'),
            $this->classFile($namespace, $blueprint->model().'StrategyContext', $this->stubs->render('strategy/context', $replacements), 'Strategy context'),
        ];
    }
}
