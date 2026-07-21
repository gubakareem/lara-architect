<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class RepositoryGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $contents = $this->stubs->render('repository', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('repository'),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('repository'),
                $blueprint->model().'Repository',
                $contents,
                'Repository',
            ),
        ];
    }
}
