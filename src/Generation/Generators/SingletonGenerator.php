<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * GoF Singleton: one shared instance with a blocked constructor. Prefer
 * Laravel's container singleton binding in production; this scaffold is
 * for explicit Singleton semantics and portfolio clarity.
 */
class SingletonGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('singleton');
        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
        ];

        return [
            $this->classFile(
                $namespace,
                $blueprint->model().'Registry',
                $this->stubs->render('singleton/registry', $replacements),
                'Singleton registry',
            ),
        ];
    }
}
