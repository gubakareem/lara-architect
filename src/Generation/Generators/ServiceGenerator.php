<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class ServiceGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $contents = $this->stubs->render('service', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('service'),
            'repositoryClass' => $blueprint->namespaceFor('repository').'\\'.$blueprint->model().'Repository',
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('service'),
                $blueprint->model().'Service',
                $contents,
                'Service',
            ),
        ];
    }
}
