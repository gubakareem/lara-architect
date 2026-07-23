<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * GoF State: behaviour changes with an internal state object. Transitions
 * live on the states themselves so the context stays thin.
 */
class StateGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('state').'\\'.$blueprint->pluralModel();
        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
        ];

        return [
            $this->classFile($namespace, $blueprint->model().'State', $this->stubs->render('state/contract', $replacements), 'State contract'),
            $this->classFile($namespace, 'Draft'.$blueprint->model().'State', $this->stubs->render('state/draft', $replacements), 'Draft state'),
            $this->classFile($namespace, 'Published'.$blueprint->model().'State', $this->stubs->render('state/published', $replacements), 'Published state'),
            $this->classFile($namespace, 'Archived'.$blueprint->model().'State', $this->stubs->render('state/archived', $replacements), 'Archived state'),
            $this->classFile($namespace, $blueprint->model().'StateContext', $this->stubs->render('state/context', $replacements), 'State context'),
        ];
    }
}
