<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * CQRS read-side: List and GetById query classes.
 */
class QueryGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('query').'\\'.$blueprint->pluralModel();
        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
        ];

        return [
            $this->classFile(
                $namespace,
                'List'.$blueprint->pluralModel().'Query',
                $this->stubs->render('queries/list', $replacements),
                'List query',
            ),
            $this->classFile(
                $namespace,
                'Get'.$blueprint->model().'Query',
                $this->stubs->render('queries/get', $replacements),
                'Get query',
            ),
        ];
    }
}
